<?php

declare(strict_types=1);

namespace pocketmine\block\utils;

use pocketmine\utils\EnumTrait;

final class CauldronLiquidType{
	use EnumTrait;

	protected static function setup() : void{
		self::registerAll(
			new self("lava"),
			new self("water"),
			new self("powder_snow")
		);
	}
}