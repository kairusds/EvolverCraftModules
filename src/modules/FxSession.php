<?php

namespace modules;

use pocketmine\Player;
use pocketmine\level\particle\Particle;

class FxSession {
	
	private $player;
	private $type;
	private $particle;
	
	public function __construct(Player $player, $type, Particle $particle) {
		$this->player = $player;
		$this->type = $type;
		$this->particle = $particle;
	}
	
	public function getPlayer() {
		return $this->player;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function setType($type) {
		$this->type = $type;
	}
	
	public function getParticle() {
		return $this->particle;
	}
	
	public function setParticle(Particle $particle) {
		$this->particle = $particle;
	}
	
	public function getSaveData() {
		return [
			$this->player,
			$this->type,
			$this->particle
		];
	}
	
	public function close() {
		Main::getInstance()->removeFxSession($this);
	}
}