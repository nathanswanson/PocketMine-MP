<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\Cauldron as TileCauldron;
use pocketmine\block\utils\CauldronLiquidType;
use pocketmine\item\Item;
use pocketmine\item\LiquidBucket;
use pocketmine\item\Potion;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Cauldron extends Transparent{

	protected int $fillLevel;

	protected CauldronLiquidType $cauldronLiquid;

	private ?Potion $potion;

	public function __construct(BlockIdentifier $idInfo, string $name, BlockBreakInfo $breakInfo){
		$this->fillLevel = 7;
		$this->cauldronLiquid = CauldronLiquidType::WATER();
		parent::__construct($idInfo, $name, $breakInfo);
	}

	public function getStateBitmask() : int{
		return 0b11111;
	}

	public function readStateFromWorld() : void{
		parent::readStateFromWorld();
		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileCauldron) {
			$tile->getPotion();
		}
	}

	public function writeStateToWorld() : void{
		parent::writeStateToWorld();
		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileCauldron) {
			$tile->setPotion(null);
		}
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->fillLevel = $stateMeta & 0x04;
		#0x24
		$this->cauldronLiquid = CauldronLiquidType::POWDER_SNOW();
	}

	protected function writeStateToMeta() : int{
		return 0b01111;
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		//if($item instanceof LiquidBucket) {
			$this->fillLevel = 6;
		//}

		return true;
	}
}