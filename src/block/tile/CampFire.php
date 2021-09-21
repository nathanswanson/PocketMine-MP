<?php

namespace pocketmine\block\tile;

use pocketmine\block\Planks;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;

class CampFire extends Spawnable {
	public const TAG_ITEM_TIME = "itemTime";
	public const TAG_ITEMS = "item";

	/** @var int[] */
	private $itemTime = [];

	/** @var Item[] */
	private $items = [];

	protected function addAdditionalSpawnData(CompoundTag $nbt) : void{
		//Arrays are left null to save space.
	}

	public function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setIntArray(self::TAG_ITEM_TIME, $this->itemTime);
		for ($itemIDX = 0; $itemIDX < count($this->items);$itemIDX++) {
			$nbt->setTag(self::TAG_ITEMS."<$itemIDX>", ($this->items[$itemIDX]->nbtSerialize()));
		}
	}

	public function readSaveData(CompoundTag $nbt) : void{
			$itemTime = $nbt->getIntArray(self::TAG_ITEM_TIME);
			for($itemIDX = 0; $itemIDX < count($this->items);$itemIDX++) {
				$this->items[$itemIDX] = Item::nbtDeserialize($nbt->getTag(self::TAG_ITEMS."<$itemIDX>"));
			}
	}

	public function addFood(Item $food): bool {
		if(count($this->items) < 3) {
			$this->items[count($this->items)] = $food;
			$this->itemTime[count($this->itemTime)] = 0;

		}

		return true;
	}
}