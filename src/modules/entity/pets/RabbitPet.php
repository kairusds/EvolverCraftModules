<?php

namespace modules\entity\pets;

use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

class RabbitPet extends Pet {
	
	const NETWORK_ID = 18;

	const DATA_RABBIT_TYPE = 18;
	const DATA_JUMP_TYPE = 19;

	const TYPE_BROWN = 0;
	const TYPE_WHITE = 1;
	const TYPE_BLACK = 2;
	const TYPE_BLACK_WHITE = 3;
	const TYPE_GOLD = 4;
	const TYPE_SALT_PEPPER = 5;
	const TYPE_KILLER_BUNNY = 99;

	public $height = 0.5;
	public $width = 0.5;
	public $length = 0.5;
	
	public function getName() {
		return "Rabbit Pet";
	}
	
	public function __construct(FullChunk $chunk, CompoundTag $nbt) {
		if(!isset($nbt->RabbitType)) {
			$nbt->RabbitType = new ByteTag("RabbitType", $this->getRandomRabbitType());
		}
		parent::__construct($chunk, $nbt);
		$this->setDataProperty(self::DATA_RABBIT_TYPE, self::DATA_TYPE_BYTE, $this->getRabbitType());
	}

	public function getRandomRabbitType() {
		$arr = [0, 1, 2, 3, 4, 5, 99];
		return $arr[mt_rand(0, count($arr) - 1)];
	}

	public function setRabbitType(int $type) {
		$this->namedtag->RabbitType = new ByteTag("RabbitType", $type);
	}

	public function getRabbitType() {
		return (int) $this->namedtag["RabbitType"];
	}
	
}