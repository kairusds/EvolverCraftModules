<?php

namespace modules\task;

use pocketmine\scheduler\PluginTask;
use pocketmine\math\Vector3;
use modules\Main;

class DropItemsTask extends PluginTask {
	
	public function __construct(Main $owner) {
		parent::__construct($owner);
	}
	
	public function onRun($currentTick) {
		if($this->getOwner()->dropTime > 0) {
			$this->getOwner()->dropTime--;
		}
		$config = $this->getOwner()->getConfig()->getAll();
		if($this->getOwner()->dropStatus == 1) {
			$level = $this->getOwner()->getServer()->getLevelByName($config["drop-party"]["level"]);
			$duration = $this->getOwner()->dropDuration - $config["drop-party"]["duration"];
			$this->getOwner()->getServer()->broadcastTip("§9DropParty will end in §1" . $this->getOwner()->seconds2mins($duration) . "§9.");
			$this->getOwner()->dropDuration++;
			if($level !== null) {
				$rand = mt_rand(0, count($config["drop-party"]["items"]) - 1);
				$items = $config["drop-party"]["items"];
				$level->dropItem(new Vector3($config["drop-party"]["x"], $config["drop-party"]["y"], $config["drop-party"]["z"]), Item::get($items[$rand], 0, mt_rand(1, $config["drop-party"]["max-count"])));
			}else {
				$this->getOwner()->getLogger()->alert("Could not drop items, the specified level in the config cannot be found.");
			}
			if($this->getOwner()->dropDuration == $config["drop-party"]["duration"]) {
				$this->getOwner()->getServer()->broadcastMessage("§9DropParty has ended!");
				$this->getOwner()->dropStatus = 0;
				$this->getOwner()->dropDuration = 0;
				$this->getOwner()->dropTime = $config["drop-party"]["starts-in"];
			}
		}
	}
}