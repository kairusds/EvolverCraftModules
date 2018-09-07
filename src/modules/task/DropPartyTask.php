<?php

namespace modules\task;

use pocketmine\scheduler\PluginTask;
use modules\Main;

class DropPartyTask extends PluginTask {
	
	public function __construct(Main $owner) {
		parent::__construct($owner);
	}
	
	public function onRun($currentTick) {
		if($this->getOwner()->dropTime > 0) {
			$this->getOwner()->getServer()->broadcastMessage("§9DropParty will commence in §1" . $this->getOwner()->seconds2mins($this->getOwner()->dropTime) . "§9.");
		}
		if($this->getOwner()->dropTime == 0) {
			$this->getOwner()->getServer()->broadcastMessage("§9DropParty has Started!");
			$this->getOwner()->dropStatus = 1;
		}
	}
}