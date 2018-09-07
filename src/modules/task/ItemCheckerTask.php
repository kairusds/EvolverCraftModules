<?php

namespace modules\task;

use pocketmine\Server;
use pocketmine\scheduler\PluginTask;
use modules\Main;
use modules\CustomEnchantment;

class ItemCheckerTask extends PluginTask {
	
	public function __construct(Main $owner) {
		parent::__construct($owner);
	}
	
	public function onRun($currentTick) {
		foreach(Server::getInstance()->getOnlinePlayers() as $player) {
			foreach($player->getInventory()->getContents() as $content) {
				if($content->hasEnchantments()) {
					foreach($content->getEnchantments() as $ench) {
						if($this->getOwner()->isCustomEnchantment($ench->getId())) {
							$name = (string) $content->hasCustomName() ? $content->getCustomName() : $content->getName();
							$name = $name . "§r\n§7";
							$e = $ench->getId();
							if($e === 100 && strpos("Blindness", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Blindness {$en}§r\n§7";
							}elseif($e === 101 && strpos("Glowing", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Glowing {$en}§r\n§7";
							}elseif($e === 102 && strpos("Obsidian", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Obsidian Shield {$en}§r\n§7";
							}elseif($e === 103 && strpos("Gears", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Gears {$en}§r\n§7";
							}elseif($e === 104 && strpos("Spring", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Springs {$en}§r\n§7";
							}elseif($e === 105 && strpos("Confus", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Confusion {$en}§r\n§7";
							}elseif($e === 106 && strpos("Light", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Lightning {$en}§r\n§7";
							}elseif($e === 107 && strpos("Pois", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Poison {$en}§r\n§7";
							}elseif($e === 108 && strpos("Froze", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Frozen {$en}§r\n§7";
							}elseif($e === 109 && strpos("Aqua", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Aquatic {$en}§r\n§7";
							}elseif($e === 110 && strpos("Double", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Double Damage {$en}§r\n§7";
							}elseif($e === 111 && strpos("Overload", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "§cOverload {$en}§r\n§7";
							}elseif($e === 113 && strpos("Demon", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Demonforge {$en}§r\n§7";
							}elseif($e === 114 && strpos("Feather", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Featherweight {$en}§r\n§7";
							}elseif($e === 115 && strpos("Disappear", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Disappearer {$en}§r\n§7";
							}elseif($e === 116 && strpos("Lifesteal", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Lifesteal {$en}§r\n§7";
							}elseif($e === 117 && strpos("Disarmor", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Disarmor {$en}§r\n§7";
							}elseif($e === 118 && strpos("Obliteration", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Obliteration {$en}§r\n§7";
							}elseif($e === 119 && strpos("Wither", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Wither {$en}§r\n§7";
							}elseif($e === 120 && strpos("VeinGlory", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "VeinGlory {$en}§r\n§7";
							}elseif($e === 121 && strpos("Whirlpool", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "§bWhirlpool {$en}§r\n§7";
							} elseif ($e === 122 && strpos("Nutrition", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Nutrition {$en}§r\n§7";
							}elseif($e === 123 && strpos("Tornado", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "§6Tornado {$en}§r\n§7";
							}elseif($e === 124 && strpos("Armoured", $name) === false) {
								$en = CustomEnchantment::integerToRoman($ench->getLevel());
								$name .= "Armoured {$en}§r\n§7";
							}
							$content->setCustomName($name);
							$player->getInventory()->sendContents($player);
						}
						if($ench->getLevel() > Main::getEnchantMaxLevel($ench->getId())) {
							$player->getInventory()->removeItem($content);
							Server::getInstance()->broadcastMessage("§c§l» §r§fFound an abused item with an enchantment level of {$ench->getLevel()} from {$player->getName()}.");
						}
					}
				}
			}
		}
	}
}