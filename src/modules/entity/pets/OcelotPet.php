<?php

namespace modules\entity\pets;

use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class OcelotPet extends Pet {
	
	const NETWORK_ID = 22;
	
	const DATA_CAT_TYPE = 18;

	const TYPE_WILD = 0;
	const TYPE_TUXEDO = 1;
	const TYPE_TABBY = 2;
	const TYPE_SIAMESE = 3;
	
	public $width = 0.312;
	public $length = 2.188;
	public $height = 0.75;
	
	public function __construct(FullChunk $chunk, CompoundTag $nbt) {
		if(!isset($nbt->CatType)) {
			$nbt->CatType = new ByteTag("CatType", mt_rand(0, 3));
		}
		parent::__construct($chunk, $nbt);
		$this->setDataProperty(self::DATA_CAT_TYPE, self::DATA_TYPE_BYTE, $this->getCatType());
	}
	
	public function setCatType(int $type) {
		$this->namedtag->CatType = new ByteTag("CatType", $type);
	}

	public function getCatType() {
		return (int) $this->namedtag["CatType"];
	}
	
	public function getName() {
		return "Ocelot Pet";
	}
	
}