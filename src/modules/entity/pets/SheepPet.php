<?php

namespace modules\entity\pets;

use pocketmine\block\Wool;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;

class SheepPet extends SheepPet {
	
	const NETWORK_ID = 13;

	const DATA_COLOR_INFO = 16;

	public $width = 0.625;
	public $length = 1.4375;
	public $height = 1.8;
	
	public function getName() {
		return "Sheep Pet";
	}
	
	public function __construct(FullChunk $chunk, CompoundTag $nbt) {
		if(!isset($nbt->Color)) {
			$nbt->Color = new ByteTag("Color", self::getRandomColor());
		}
		parent::__construct($chunk, $nbt);
		$this->setDataProperty(self::DATA_COLOR_INFO, self::DATA_TYPE_BYTE, $this->getColor());
	}

	public static function getRandomColor() {
		$rand = "";
		$rand .= str_repeat(Wool::WHITE . " ", 20);
		$rand .= str_repeat(Wool::ORANGE . " ", 5);
		$rand .= str_repeat(Wool::MAGENTA . " ", 5);
		$rand .= str_repeat(Wool::LIGHT_BLUE . " ", 5);
		$rand .= str_repeat(Wool::YELLOW . " ", 5);
		$rand .= str_repeat(Wool::GRAY . " ", 10);
		$rand .= str_repeat(Wool::LIGHT_GRAY . " ", 10);
		$rand .= str_repeat(Wool::CYAN . " ", 5);
		$rand .= str_repeat(Wool::PURPLE . " ", 5);
		$rand .= str_repeat(Wool::BLUE . " ", 5);
		$rand .= str_repeat(Wool::BROWN . " ", 5);
		$rand .= str_repeat(Wool::GREEN . " ", 5);
		$rand .= str_repeat(Wool::RED . " ", 5);
		$rand .= str_repeat(Wool::BLACK . " ", 10);
		$arr = explode(" ", $rand);
		return intval($arr[mt_rand(0, count($arr) - 1)]);
	}

	public function getColor() {
		return (int) $this->namedtag["Color"];
	}

	public function setColor(int $color) {
		$this->namedtag->Color = new ByteTag("Color", $color);
	}
	
}