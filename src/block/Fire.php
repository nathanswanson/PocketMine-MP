<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\utils\BlockDataSerializer;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\entity\EntityCombustByBlockEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use function intdiv;
use function max;
use function min;
use function mt_rand;

class Fire extends Flowable{

	/** @var int */
	protected $age = 0;

	public function __construct(BlockIdentifier $idInfo, string $name, ?BlockBreakInfo $breakInfo = null){
		parent::__construct($idInfo, $name, $breakInfo ?? BlockBreakInfo::instant());
	}

	protected function writeStateToMeta() : int{
		return $this->age;
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->age = BlockDataSerializer::readBoundedInt("age", $stateMeta, 0, 15);
	}

	public function getStateBitmask() : int{
		return 0b1111;
	}

	public function getAge() : int{ return $this->age; }

	/** @return $this */
	public function setAge(int $age) : self{
		if($age < 0 || $age > 15){
			throw new \InvalidArgumentException("Age must be in range 0-15");
		}
		$this->age = $age;
		return $this;
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function getLightLevel() : int{
		return 15;
	}

	public function canBeReplaced() : bool{
		return true;
	}

	public function onEntityInside(Entity $entity) : bool{
		$ev = new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_FIRE, 1);
		$entity->attack($ev);

		$ev = new EntityCombustByBlockEvent($this, $entity, 8);
		if($entity instanceof Arrow){
			$ev->cancel();
		}
		$ev->call();
		if(!$ev->isCancelled()){
			$entity->setOnFire($ev->getDuration());
		}
		return true;
	}

	public function getDropsForCompatibleTool(Item $item) : array{
		return [];
	}

	public function onNearbyBlockChange() : void{
		if(!$this->getSide(Facing::DOWN)->isSolid() and !$this->hasAdjacentFlammableBlocks()){
			$this->pos->getWorld()->setBlock($this->pos, VanillaBlocks::AIR());
		}else{
			$this->pos->getWorld()->scheduleDelayedBlockUpdate($this->pos, mt_rand(30, 40));
		}
	}

	public function ticksRandomly() : bool{
		return true;
	}

	public function onRandomTick() : void{
		$down = $this->getSide(Facing::DOWN);

		$result = null;
		if($this->age < 15 and mt_rand(0, 2) === 0){
			$this->age++;
			$result = $this;
		}
		$canSpread = true;

		if(!$down->burnsForever()){
			//TODO: check rain
			if($this->age === 15){
				if(!$down->isFlammable() and mt_rand(0, 3) === 3){ //1/4 chance to extinguish
					$canSpread = false;
					$result = VanillaBlocks::AIR();
				}
			}elseif(!$this->hasAdjacentFlammableBlocks()){
				$canSpread = false;
				if(!$down->isSolid() or $this->age > 3){
					$result = VanillaBlocks::AIR();
				}
			}
		}

		if($result !== null){
			$this->pos->getWorld()->setBlock($this->pos, $result);
		}

		$this->pos->getWorld()->scheduleDelayedBlockUpdate($this->pos, mt_rand(30, 40));

		if($canSpread){
			$this->burnBlocksAround();
			$this->spreadFire();
		}
	}

	public function onScheduledUpdate() : void{
		$this->onRandomTick();
	}

	private function hasAdjacentFlammableBlocks() : bool{
		foreach(Facing::ALL as $face){
			if($this->getSide($face)->isFlammable()){
				return true;
			}
		}

		return false;
	}

	private function burnBlocksAround() : void{
		//TODO: raise upper bound for chance in humid biomes

		foreach($this->getHorizontalSides() as $side){
			$this->burnBlock($side, 300);
		}

		//vanilla uses a 250 upper bound here, but I don't think they intended to increase the chance of incineration
		$this->burnBlock($this->getSide(Facing::UP), 350);
		$this->burnBlock($this->getSide(Facing::DOWN), 350);
	}

	private function burnBlock(Block $block, int $chanceBound) : void{
		if(mt_rand(0, $chanceBound) < $block->getFlammability()){
			$ev = new BlockBurnEvent($block, $this);
			$ev->call();
			if(!$ev->isCancelled()){
				$block->onIncinerate();

				if(mt_rand(0, $this->age + 9) < 5){ //TODO: check rain
					$fire = clone $this;
					$fire->age = min(15, $fire->age + (mt_rand(0, 4) >> 2));
					$this->spreadBlock($block, $fire);
				}else{
					$this->pos->getWorld()->setBlock($block->pos, VanillaBlocks::AIR());
				}
			}
		}
	}

	private function spreadFire() : void{
		$world = $this->pos->getWorld();
		$difficulty7 = $world->getDifficulty() * 7;
		$age30 = $this->age + 30;

		for($y = -1; $y <= 4; ++$y){
			//Higher blocks have a lower chance of catching fire
			$randomBound = 100 + ($y > 1 ? ($y - 1) * 100 : 0);

			for($z = -1; $z <= 1; ++$z){
				for($x = -1; $x <= 1; ++$x){
					if($x === 0 and $y === 0 and $z === 0){
						continue;
					}

					$block = $world->getBlockAt($this->pos->x + $x, $this->pos->y + $y, $this->pos->z + $z);
					if($block->getId() !== BlockLegacyIds::AIR){
						continue;
					}

					//TODO: fire can't spread if it's raining in any horizontally adjacent block, or the current one

					$encouragement = 0;
					foreach($block->getAllSides() as $blockSide){
						$encouragement = max($encouragement, $blockSide->getFlameEncouragement());
					}

					if($encouragement <= 0){
						continue;
					}

					$maxChance = intdiv($encouragement + 40 + $difficulty7, $age30);
					//TODO: max chance is lowered by half in humid biomes

					if($maxChance > 0 and mt_rand(0, $randomBound - 1) <= $maxChance){
						$new = clone $this;
						$new->age = min(15, $this->age + (mt_rand(0, 4) >> 2));
						$this->spreadBlock($block, $new);
					}
				}
			}
		}
	}

	private function spreadBlock(Block $block, Block $newState) : void{
		$ev = new BlockSpreadEvent($block, $this, $newState);
		$ev->call();
		if(!$ev->isCancelled()){
			$this->pos->getWorld()->setBlock($block->getPos(), $ev->getNewState());
		}
	}
}
