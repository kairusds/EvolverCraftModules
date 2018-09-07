<?php

namespace modules;

use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\entity\{
	Effect,
	Living
};
use pocketmine\math\Vector3;
use pocketmine\event\{
	block\BlockBreakEvent,
	player\PlayerMoveEvent,
	entity\EntityDamageEvent,
	entity\EntityDamageByEntityEvent
};

class Booster implements Listener {
	
	const ONE_DAY = 20 * 60 * 24;
	const THREE_DAYS = 20 * 60 * 24 * 3;
	const ONE_WEEK = 20 * 60 * 24 * 7;
	
	const TERMINATOR = 0;
	const OWL = 1;
	const FLASH = 2;
	const DRILLER = 3;
	const MAGE = 4;
	const MUTANT = 5;
	const WITHER = 6;
	const VOLT = 7;
	
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->config = new Config($this->plugin->getDataFolder() . "boosters.yml", Config::YAML);
	}
	
	public static function addSession(Player $player, $type) {
		switch($type) {
			case self::TERMINATOR:
				$player->addEffect(Effect::getEffect(Effect::STRENGTH)->setDuration(self::ONE_DAY)->setAmplifier(1));
				$this->plugin->getEconomy()->reduceMoney($player, 30000);
				$this->config->setNested("sessions.$player->getName().$type", self::ONE_DAY);
				break;
			case self::OWL:
				$player->addEffect(Effect::getEffect(Effect::NIGHT_VISION)->setDuration(self::THREE_DAYS));
				$this->plugin->getEconomy()->reduceMoney($player, 50000);
				$this->config->setNested("sessions.$player->getName().$type", self::THREE_DAYS);
				break;
			case self::FLASH:
				$this->plugin->getEconomy()->reduceMoney($player, 20000);
				$this->config->setNested("sessions.$player->getName().$type", self::THREE_DAYS);
				break;
			case self::DRILLER:
				$this->plugin->getEconomy()->reduceMoney($player, 40000);
				$this->config->setNested("sessions.$player->getName().$type", self::THREE_DAYS);
				break;
			case self::MAGE:
				$player->addEffect(Effect::getEffect(Effect::STRENGTH)->setDuration(self::ONE_WEEK * 2)->setAmplifier(1));
				$player->addEffect(Effect::getEffect(Effect::REGENERATION)->setDuration(self::ONE_WEEK * 2)->setAmplifier(4));
				$this->plugin->getEconomy()->reduceMoney($player, 200000);
				$this->config->setNested("sessions.$player->getName().$type", self::ONE_WEEK * 2);
				break;
			case self::MUTANT:
				foreach([
					Effect::getEffect(Effect::REGENERATION)->setDuration(self::ONE_WEEK * 3)->setAmplifier(8),
					Effect::getEffect(Effect::ABSORPTION)->setDuration(self::ONE_WEEK * 3)->setAmplifier(1),
					Effect::getEffect(Effect::DAMAGE_RESISTANCE)->setDuration(self::ONE_WEEK * 3)->setAmplifier(1),
					Effect::getEffect(Effect::FIRE_RESISTANCE)->setDuration(self::ONE_WEEK * 3)->setAmplifier(1)
				] as $effect) {
					$player->addEffect($effect);
				}
				$this->plugin->getEconomy()->reduceMoney($player, 217500);
				$this->config->setNested("sessions.$player->getName().$type", self::ONE_DAY * 3);
				break;
			case self::WITHER:
				$this->plugin->getEconomy()->reduceMoney($player, 75000);
				$this->config->setNested("sessions.$player->getName().$type", self::THREE_DAYS * 2);
				break;
			case self::VOLT:
				$this->plugin->getEconomy()->reduceMoney($player, 750000);
				$this->config->setNested("sessions.$player->getName().$type", self::THREE_DAYS * 2);
				break;
			default:
				return false;
		}
	}
	
	public function onMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		if($this->hasBooster($player, self::FLASH) && $player->isSprinting()) {
			$player->addEffect(Effect::getEffect(Effect::SPEED)->setDuration(20)->setAmplifier(0));
		}
	}
	
	public function onDamage(EntityDamageEvent $event) {
		if($event instanceof EntityDamageByEntityEvent && !$event->isCancelled()) {
			$damager = $event->getDamager();
			$victim = $event->getEntity();
			if($damager instanceof Player && $victim instanceof Living) {
				foreach([$damager, $victim] as $player) {
					if($player instanceof Player && $this->hasBooster($player, self::TERMINATOR)) {
						$player->addEffect(Effect::getEffect(Effect::SPEED)->setDuration(100)->setAmplifier(4));
					}
				}
				if($this->hasBooster($damager, self::WITHER)) {
					foreach([
						Effect::getEffect(Effect::BLINDNESS)->setDuration(40)->setAmplifier(0),
						Effect::getEffect(Effect::WITHER)->setDuration(8)->setAmplifier(1)
					] as $effect) {
						$victim->addEffect($effect);
					}
				}
				if($this->hasBooster($damager, self::VOLT) && $this->plugin->chance(mt_rand(10, 20))) {
					$victim->getLevel()->spawnLightning(new Vector3($victim->x, $victim->y, $victim->z));
					$victim->setHealth($victim->getHealth() - 4);
				}
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $event) {
		$player = $event->getPlayer();
		if($this->hasBooster($player, self::DRILLER)) {
			$player->addEffect(Effect::getEffect(Effect::HASTE)->setDuration(100));
		}
	}
	
	public function hasBooster($player, $booster) {
		if($player instanceof Player) {
			$player = $player->getName();
		}
		strtolower($player);
		return $this->config->getNested("sessions.$player.$booster") !== null;
	}
}