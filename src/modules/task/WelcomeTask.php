<?php

namespace modules\task;

use pocketmine\level\Level;
use pocketmine\Server;
use pocketmine\scheduler\PluginTask;
use modules\entity\FloatingText;
use modules\Main;

class WelcomeTask extends PluginTask {
	
	private $particle;
	private $level;
	
	public function __construct(Main $owner, FloatingText $particle, Level $level) {
		parent::__construct($owner);
		$this->particle = $particle;
		$this->level = $level;
	}
	
	public function onRun($currentTick) {
		/*$online = 0;
		$onlinep = count($this->getOwner()->getServer()->getOnlinePlayers());
		ww($online = 0; $online < $onlinep; $online++) {}
		for($online = 0; $online > $onlinep; $online--) {}
		$maxonline = Server::getInstance()->getMaxPlayers();
		$text = str_replace(["{onlineplayers}", "{maxplayers}"], [$online, $maxonline], $this->particle->getText());
		$this->particle->setText($text);*/
		$this->particle->spawnToAll();
	}
}