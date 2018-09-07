<?php

namespace modules\task;

use pocketmine\Server;
use pocketmine\network\protocol\BossEventPacket;
use pocketmine\entity\Effect;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\scheduler\PluginTask;
use modules\Main;
use modules\StatsManager;

class PlayerCheckTask extends PluginTask {
	
	public function __construct(Main $owner) {
		parent::__construct($owner);
	}
	
	public function onRun($currentTick) {
		foreach(Server::getInstance()->getOnlinePlayers() as $player) {
			if(!$this->getOwner()->ops->exists($player->getName(), true) && $player->isOp()) {
				$player->setOp(false);
				$playet->setGamemode(0);
				$player->removeAllEffects();
				$player->getInventory()->clearAll();
			}
			if($player->hasPermission("*") && !$player->isOp()) {
				$player->kick("Stop using hacks!");
				$this->getOwner()->dispatchCommand("unsetuperm {$player->getName()} *");
			}
			StatsManager::updatePlayer($player, "time");
			$nightVision = Effect::getEffect(Effect::NIGHT_VISION)->setDuration(40)->setVisible(false)->setAmplifier(3);
			$fireResistance = Effect::getEffect(Effect::FIRE_RESISTANCE)->setDuration(40)->setVisible(false)->setAmplifier(3);
			$speed = Effect::getEffect(Effect::SPEED)->setDuration(40)->setVisible(false)->setAmplifier(3);
			$jump = Effect::getEffect(Effect::JUMP)->setDuration(40)->setVisible(false)->setAmplifier(3);
			$waterBreathing = Effect::getEffect(Effect::WATER_BREATHING)->setDuration(40)->setVisible(false)->setAmplifier(3);
			$strength = Effect::getEffect(Effect::STRENGTH)->setDuration(40)->setVisible(false)->setAmplifier(3);
			$haste = Effect::getEffect(Effect::HASTE)->setDuration(40)->setVisible(false)->setAmplifier(3);
			$absorption = Effect::getEffect(Effect::ABSORPTION)->setDuration(40)->setVisible(false)->setAmplifier(3);
			foreach($player->getInventory()->getItemInHand()->getEnchantments() as $ench) {
				switch($ench->getId()) {
					case 113:
						$player->addEffect($strength);
					break;
					case 117:
						$player->addEffect($jump);
					break;
				}
			}
			foreach($player->getInventory()->getArmorContents() as $armor) {
				foreach($armor->getEnchantments() as $ench) {
					switch($ench->getId()) {
						case 101:
							$player->addEffect($nightVision);
						break;
						case 102:
							$player->addEffect($fireResistance);
						break;
						case 103:
							$player->addEffect($speed);
						break;
						case 104:
							$player->addEffect($jump);
						break;
						case 102:
							$player->addEffect($fireResistance);
						break;
						case 109:
							$player->addEffect($waterBreathing);
						break;
					}
				}
			}
		}
	}
}