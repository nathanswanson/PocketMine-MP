<?php

declare(strict_types=1);

namespace pocketmine\block\tile;

use pocketmine\block\PackedIce;
use pocketmine\inventory\SimpleInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;

class Cauldron extends Spawnable implements Container, Nameable{
	use ContainerTrait;
	use NameableTrait;

	public const TAG_POTION_ID = "PotionID";
	public const TAG_POTION_TYPE = "PotionType";
	public const TAG_CUSTOM_COLOR = "CustomColor";

	/** @var SimpleInventory */
	protected SimpleInventory $inventory;

	/** @var int  */
	private int $potionID = -1;

	/** @var int  */
	private int $potionType = -1;

	/** @var int|null */
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

	/**
	 * @return SimpleInventory
	 */
	public function getRealInventory(): SimpleInventory{
		return $this->getInventory();
	}

	/**
	 * @return SimpleInventory
	 */
	public function getInventory(): SimpleInventory{
		return $this->inventory;
	}

	/**
	 * @return string
	 */
	public function getDefaultName() : string{
		return "Cauldron";
	}

}