<?php

namespace modules;

use pocketmine\{
	Player, IPlayer, Server
};

class StatsManager {
	
	private static $db;
	private static $plugin;
	
	public static function init() {
		if(!self::$plugin instanceof Main) {
			self::$plugin = Server::getInstance()->getPluginManager()->getPlugin("EvolvedCraftModules");
		}
		if(!self::$db instanceof \SQLite3) {
			self::$db = new \SQLite3(self::$plugin->getDataFolder() . "stats.db");
			self::$db->exec("CREATE TABLE IF NOT EXISTS master (name VARCHAR(255) PRIMARY KEY, kills INT DEFAULT 0, deaths INT DEFAULT 0, time INT DEFAULT 0);");
		}
	}
	
	public static function createData(IPlayer $player) {
		if(!self::playerExists($player)) {
			$name = strtolower($player->getName());
			self::$db->query("INSERT INTO master (name, kills, deaths, time) VALUES ('$name', '0', '0', '0');");
		}
	}
	
	public static function playerExists(IPlayer $player) {
		$name = strtolower($player->getName());
		$result = self::$db->query("SELECT * FROM master WHERE name='$name';");
		$array = $result->fetchArray(SQLITE3_ASSOC);
		return empty($array) == false;
	}
	
	public static function removePlayer(IPlayer $player) {
		$name = strtolower($player->getName());
		if(self::playerExists($player)) {
			self::$db->query("DELETE FROM master WHERE name = '$name';");
		}else {
			return null;
		}
	}
	
	public static function getStats(IPlayer $player) {
		$msg = "";
		if(self::playerExists($player)) {
			$msg = "§l§a»§8------- §r§fStats §8-------§a«§r\n";
			$kills = self::getPlayer($player)["kills"];
			$deaths = self::getPlayer($player)["deaths"];
			$time = self::$plugin->seconds2clock(self::getPlayer($player)["time"]);
			$kdr = 0;
			if($kills >= 1 && $deaths >= 1) {
				$kdr = "§9" . round(($kills / $deaths), 3);
			}else {
				$kdr = "§cN§7/§cA";
			}
			$msg .= "§6Kills§7: §9$kills §r\n";
			$msg .= "§6Deaths§7: §9$deaths §r\n";
			$msg .= "§6Time Played§7: §9$time §r\n";
			$msg .= "§6Kill/Death Ratio§7: $kdr §r\n";
			return $msg;
		}
		return null;
	}
	
	public static function getTopKills() {
		$msg = "§l§a»§8------- §r§fTop Killers §8-------§a«§r\n";
		$i = 1;
		$result = self::$db->query("SELECT name FROM master ORDER BY kills DESC LIMIT 10");
		while($row = $result->fetchArray(SQLITE3_ASSOC)) {
			$name = $row["name"];
			$offlinePlayer = Server::getInstance()->getOfflinePlayer($name);
			$kills = self::getPlayer($offlinePlayer)["kills"];
			$rank = $i;
			$msg .= "§b" . $rank . "§7. §2§o" . $name . " §r§7- §9" . $kills . "§r\n";
			$i++;
		}
		return $msg;
	}
	
	public static function getPlayer(IPlayer $player) {
		$name = strtolower($player->getName());
		$result = self::$db->query("SELECT * FROM master WHERE name = '$name';");
		$data = $result->fetchArray(SQLITE3_ASSOC);
		if(isset($data["name"]) && strtolower($data["name"]) === $name) {
			unset($data["name"]);
			return $data;
		}
		return null;
	}
	
	public static function updatePlayer(IPlayer $player, $type) {
		$name = strtolower($player->getName());
		self::$db->query("UPDATE master SET " . $type . " = " . $type . " + 1 WHERE name = '$name';");
	}
}