<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Potion;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Cauldron extends Spawnable implements Container, Nameable{
	use ContainerTrait;
	use NameableTrait;

	public const TAG_POTION_ID = "PotionID";
	public const TAG_POTION_TYPE = "PotionType";
	public const TAG_CUSTOM_COLOR = "CustomColor";

	protected SimpleInventory $inventory;

	private int $potionID = -1;

	private int $potionType = -1;

	private ?int $customColor;

	public function __construct(World $world, Vector3 $pos){
		$this->inventory = new SimpleInventory(3);
		parent::__construct($world, $pos);
	}

	public function readSaveData(CompoundTag $nbt) : void{
		$this->potionID = $nbt->getShort(self::TAG_POTION_ID, $this->potionID);
		$this->potionType = $nbt->getShort(self::TAG_POTION_TYPE, $this->potionType);
		if($this->potionType != -1) {
			$this->customColor = $nbt->getInt(self::TAG_CUSTOM_COLOR, $this->customColor);
		}

		$this->loadName($nbt);
		$this->loadItems($nbt);
	}

	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setShort(self::TAG_POTION_ID, $this->potionID);
		$nbt->setShort(self::TAG_POTION_TYPE, $this->potionType);
		if($this->potionType != -1) {
			$nbt->setShort(self::TAG_CUSTOM_COLOR, $this->customColor);
		}

		$this->saveName($nbt);
		$this->saveItems($nbt);
	}

	public function getRealInventory(): SimpleInventory{
		return $this->getInventory();
	}

	public function getInventory(): SimpleInventory{
		return $this->inventory;
	}

	public function getPotion(): ?Potion {
		return ($this->potionType == -1 && $this->potionID == -1 ? null : new Potion()) ;
	}

	public function setPotion(?Potion $potion): void {

	}

	public function getDefaultName() : string{
		return "Cauldron";
	}

}