<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\Transparent;
use pocketmine\block\tile\CampFire as TileCampFire;
use pocketmine\block\utils\BlockDataSerializer;
use pocketmine\block\utils\FacesOppositePlacingPlayerTrait;
use pocketmine\block\utils\HorizontalFacingTrait;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityCombustByBlockEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class CampFire extends Transparent{
	use HorizontalFacingTrait;
	use FacesOppositePlacingPlayerTrait;

	/** @var int[] */
	protected $itemTime;

	/** @var Item[] */
	protected $items;

	protected bool $isLit = false;

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->facing = BlockDataSerializer::readLegacyHorizontalFacing($stateMeta & 0x03);
		$this->isLit = ($stateMeta & 0x04) !== 0x04;
	}

	public function readStateFromWorld() : void{
		parent::readStateFromWorld();
	}

	protected function writeStateToMeta() : int{
		return BlockDataSerializer::writeLegacyHorizontalFacing($this->facing) | ($this->isLit ? 0 : 0x04);
	}

	public function writeStateToWorld() : void{
		parent::writeStateToWorld();
	}

	public function getStateBitmask() : int{
		return 0b111;
	}

	public function hasEntityCollision() : bool{
		return true;
	}

	public function onEntityInside(Entity $entity) : bool{
		if($this->isLit()){
			$ev = new EntityDamageByBlockEvent($this, $entity, EntityDamageEvent::CAUSE_FIRE, 1);
			$entity->attack($ev);
			//TODO: find actual duration 8 is arbitrary.
			$ev = new EntityCombustByBlockEvent($this, $entity, 8);
			if($entity instanceof Arrow){
				$ev->cancel();
			}
			$ev->call();
			if(!$ev->isCancelled()){
				$entity->setOnFire($ev->getDuration());
			}

		}
		return true;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileCampFire) {
			$tile->addFood($item);
		}
		return parent::onInteract($item, $face, $clickVector, $player);
	}


}