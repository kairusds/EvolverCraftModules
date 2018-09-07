<?php

namespace modules\entity\pets;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\CompoundTag;

class VillagerPet extends Pet {
	
	const PROFESSION_FARMER = 0;
	const PROFESSION_LIBRARIAN = 1;
	const PROFESSION_PRIEST = 2;
	const PROFESSION_BLACKSMITH = 3;
	const PROFESSION_BUTCHER = 4;

	const NETWORK_ID = 15;

	const DATA_PROFESSION_ID = 16;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;

	public function getName() {
		return "Villager Pet";
	}

	public function __construct(FullChunk $chunk, CompoundTag $nbt) {
		if(!isset($nbt->Profession)) {
			$nbt->Profession = new ByteTag("Profession", mt_rand(0, 4));
		}
		parent::__construct($chunk, $nbt);
		$this->setDataProperty(self::DATA_PROFESSION_ID, self::DATA_TYPE_BYTE, $this->getProfession());
	}
	
	public function setProfession(int $profession) {
		$this->namedtag->Profession = new ByteTag("Profession", $profession);
	}

	public function getProfession() {
		$pro = (int) $this->namedtag["Profession"];
		return min(4, max(0, $pro));
	}
	
	public function isBaby() {
		return $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_BABY);
	}
	
}