<?php

namespace modules;

use pocketmine\{
	Player,
	Server
};

class Messages {
	
	const PREFIX = "§l§8[§r§aE§2C§8§l]§r§f";
	
	public static function broadcast($type = "chat", $message) {
		if($type == "chat") {
			Server::getInstance()->broadcastMessage(self::PREFIX . " §9" . $message);
		}elseif($type == "tip") {
			Server::getInstance()->broadcastTip(self::PREFIX . " §9" . $message);
		}elseif($type == "popup") {
			Server::getInstance()->broadcastPopup(self::PREFIX . " §9" . $message);
		}
	}
	
	public static function send(Player $player, $type = "chat", $message) {
		if($type == "chat") {
			$player->sendMessage(self::PREFIX . " §9" . $message);
		}elseif($type == "tip") {
			$player->sendTip(self::PREFIX . " §9" . $message);
		}elseif($type == "popup") {
			$player->sendPopup(self::PREFIX . " §9" . $message);
		}elseif($type == "warning") {
			$player->sendMessage(self::PREFIX . " §c" . $message);
		}elseif($type == "ce") {
			$player->sendMessage(self::PREFIX . " §2§l" . $message);
		}
	}
}