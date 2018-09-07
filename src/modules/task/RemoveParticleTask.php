<?php

namespace modules\task;

use pocketmine\level\Level;
use pocketmine\scheduler\PluginTask;
use pocketmine\level\particle\FloatingTextParticle;
use modules\Main;

class RemoveParticleTask extends PluginTask {
	
	private $particle;
	private $level;
	
	public function __construct(Main $owner, FloatingTextParticle $particle, Level $level) {
		parent::__construct($owner);
		$this->particle = $particle;
		$this->level = $level;
	}
	
	public function onRun($currentTick) {
		$this->particle->setInvisible();
		$this->level->addParticle($this->particle);
	}
}