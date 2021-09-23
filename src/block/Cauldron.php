<?php

declare(strict_types=1);

namespace pocketmine\block;

use pocketmine\block\tile\Cauldron as TileCauldron;
use pocketmine\item\Item;
use pocketmine\item\LiquidBucket;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class Cauldron extends Transparent{

	protected int $fillLevel;

	protected string $cauldronLiquid;

	public function __construct(BlockIdentifier $idInfo, string $name, BlockBreakInfo $breakInfo){
		//$this->cauldronLiquid
		parent::__construct($idInfo, $name, $breakInfo);
	}

	public function readStateFromWorld() : void{
		parent::readStateFromWorld();
		$tile = $this->position->getWorld()->getTile($this->position);
		if($tile instanceof TileCauldron) {

		}
	}

	public function readStateFromData(int $id, int $stateMeta) : void{
		$this->fillLevel = $stateMeta;
		$this->cauldronLiquid = "water";
	}

	public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($item instanceof LiquidBucket) {

		}

		return true;
	}
}