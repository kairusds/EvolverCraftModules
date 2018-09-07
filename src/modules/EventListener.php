<?php

namespace modules;

use pocketmine\entity\Entity;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\item\{
	Armor,
	Item,
	Tool
};
use pocketmine\item\enchantment\Enchantment;
use pocketmine\event\block\{
	BlockBreakEvent,
	BlockPlaceEvent,
	SignChangeEvent
};
use pocketmine\event\level\{
	ChunkLoadEvent
};
use pocketmine\event\player\{
	PlayerGameModeChangeEvent,
	PlayerCommandPreprocessEvent,
	PlayerLoginEvent,
	PlayerJoinEvent,
	PlayerChatEvent,
	PlayerInteractEvent,
	PlayerItemHeldEvent,
	PlayerDropItemEvent,
	PlayerPreLoginEvent,
	PlayerMoveEvent,
	PlayerDeathEvent,
	PlayerQuitEvent,
	PlayerUseFishingRodEvent,
	PlayerTextPreSendEvent
};
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\{
	InteractPacket,
	ResourcePacksInfoPacket,
	ChangeDimensionPacket
};
use pocketmine\level\sound\ExpPickupSound;
use modules\task\RemoveParticleTask;
use pocketmine\entity\{
	Living,
	Effect
};
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\event\entity\{
	EntityCombustEvent,
	EntityDamageEvent,
	EntityDamageByEntityEvent,
	EntityRegainHealthEvent,
	EntityTeleportEvent,
	EntityLevelChangeEvent
};
use pocketmine\event\inventory\{
	InventoryTransactionEvent,
	InventoryCloseEvent
};
use pocketmine\inventory\ChestInventory;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use modules\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\nbt\tag\{
	CompoundTag,
	ListTag,
	DoubleTag,
	FloatTag,
	IntTag
};
use pocketmine\level\generator\biome\Biome;
use pocketmine\tile\{
	Chest,
	Tile,
	MobSpawner
};

class EventListener implements Listener {
	
	private $plugin;
	
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
	}
	
	/**
	 * @ignoreCancelled true
	 *
	 * @priority HIGHEST
	 */
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		$this->plugin->spawnWh($player);
		$this->plugin->spawnTf($player);
	}
	
	/**
	 * @ignoreCancelled true
	 *
	 * @priority HIGHEST
	 */
	public function onLogin(PlayerLoginEvent $event) {
		$player = $event->getPlayer();
		foreach($player->getInventory()->getArmorContents() as $armor) {
			$this->plugin->updateOverload($player, $armor);
		}
		if($player->hasPermission("ec.inventorysize.100")) {
			$player->getInventory()->setSize(100);
		}elseif($player->hasPermission("ec.inventorysize.250")) {
			$player->getInventory()->setSize(250);
		}elseif($player->hasPermission("ec.inventorysize.500")) {
			$player->getInventory()->setSize(500);
		}elseif($player->hasPermission("ec.inventorysize.1000")) {
			$player->getInventory()->setSize(1000);
		}
		StatsManager::createData($player);
		$this->plugin->flyCheck[$player->getName()] = 0;
		$this->plugin->addBountyConfig($player);
		$this->plugin->updateHealthBar($player);
	}
	
	/**
	 * @ignoreCancelled true
	 *
	 * @priority HIGHEST
	 */
	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		unset($this->plugin->flyCheck[$player->getName()]);
		if(isset($this->plugin->xray[$player->getName()])){
			$this->plugin->revertDisplay($this->plugin->xray[$player->getName()], $player);
			unset($this->plugin->xray[$player->getName()]);
		}
		$this->plugin->despawnWh($player);
		$this->plugin->despawnTf($player);
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onLevelChange(EntityLevelChangeEvent $event) {
		$origin = $event->getOrigin();
		$target = $event->getTarget();
		$server = $this->plugin->getServer();
		$player = $event->getEntity();
		if(!$player instanceof Player) {
			return;
		}
		$tFHLevel = $server->getLevelByName($this->plugin->topfacs->get("level"));
		if($target != $server->getDefaultLevel()) {
			$this->plugin->despawnWh($player);
		}
		if($target != $tFHLevel) {
			$this->plugin->despawnTf($player);
		}
		if($target == $server->getDefaultLevel()) {
			$this->plugin->spawnWh($player);
		}
		if($target == $tFHLevel) {
			$this->plugin->spawnTf($player);
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$item = $event->getItem();
		$tag = $item->getNamedTag();
		if($item instanceof Armor) {
			$armor = $player->getInventory()->getArmorItem($type = $item->getArmorType());
			$player->getInventory()->setArmorItem($type, $item);
			$player->getInventory()->setItem($player->getInventory()->getHeldItemSlot(), $armor);
			$player->getInventory()->sendContents($player);
			$player->getInventory()->sendArmorContents($player);
		}
		if(isset($this->plugin->xray[$player->getName()])){
			$this->plugin->revertDisplay($this->plugin->xray[$player->getName()], $player);
			unset($this->plugin->xray[$player->getName()]);
			$event->setCancelled();
			$player->sendMessage("§a§l» §r§fDisabling xray...");
		}
		if(($this->plugin->isAtSpawn($player) || $this->plugin->isAtWarzone($player)) && in_array($item->getId(), $this->plugin->blockedItems)) {
			if(!$player->hasPermission("ec.limitations.gamemode")) {
				$event->setCancelled();
			}
		}
		if($item->getId() == Item::COMPASS && isset($tag->CompassEnergy) && $tag["CompassEnergy"] <= 0) {
			$player->getInventory()->removeItem(Item::get(Item::COMPASS, $item->getDamage(), 1));
			$event->setCancelled();
		}
		if($item->getId() == Item::COMPASS && isset($tag->CompassEnergy)) {
			$block = $player->getTargetBlock(120);
			$vector = $block->add(0.5, 2, 0.5);
			$level = $player->getLevel();
			if($level->getBlock($block->add(-1, 0, 0))->getId() !== 0) {
				$vector = $vector->add(1, 0, 0);
			}
			if($level->getBlock($block->add(1, 0, 0))->getId() !== 0) {
				$vector = $vector->add(-1, 0, 0);
			}
			if($level->getBlock($block->add(0, 0, -1))->getId() !== 0) {
				$vector = $vector->add(0, 0, 1);
			}
			if($level->getBlock($block->add(0, 0, 1))->getId() !== 0) {
				$vector = $vector->add(0, 0, -1);
			}
			$tag["CompassEnergy"]--;
			$player->getInventory()->setItemInHand($item);
			$player->sendTip("§l§a» §r§fEnergy Left: " . $tag["CompassEnergy"] . " §l§a«");
			$player->setPosition($vector);
		}
		if(isset($this->plugin->setSpawn[$player->getName()])) {
			if(empty($this->plugin->setSpawn[$player->getName()])) {
				$this->plugin->setSpawn[$player->getName()] = $block->x . ":" . $block->z;
				Messages::send($player, "chat", "First position of the spawn area has been selected.");
				Messages::send($player, "chat", "Now select the second position of the spawn area.");
			}else {
				$xz1 = explode(":", $this->plugin->setSpawn[$player->getName()]);
				$this->plugin->getConfig()->set("spawnX1", $xz1[0]);
				$this->plugin->getConfig()->set("spawnZ1", $xz1[1]);
				$this->plugin->getConfig()->set("spawnX2", $block->x);
				$this->plugin->getConfig()->set("spawnZ2", $block->z);
				$this->plugin->getConfig()->set("spawnLevel", $player->getLevel()->getName());
				$this->plugin->getConfig()->save();
				unset($this->plugin->setSpawn[$player->getName()]);
				Messages::send($player, "chat", "Second position of the spawn area has been selected.");
				Messages::send($player, "chat", "The spawn area's positions are now set.");
			}
		}
		if(isset($this->plugin->setWarzone[$player->getName()])) {
			if(empty($this->plugin->setWarzone[$player->getName()])) {
				$this->plugin->setWarzone[$player->getName()] = $block->x . ":" . $block->z;
				Messages::send($player, "chat", "First position of the warzone area has been selected.");
				Messages::send($player, "chat", "Now select the second position of the warzone area.");
			}else {
				$xz1 = explode(":", $this->plugin->setWarzone[$player->getName()]);
				$this->plugin->getConfig()->set("warzoneX1", $xz1[0]);
				$this->plugin->getConfig()->set("warzoneZ1", $xz1[1]);
				$this->plugin->getConfig()->set("warzoneX2", $block->x);
				$this->plugin->getConfig()->set("warzoneZ2", $block->z);
				$this->plugin->getConfig()->set("warzoneLevel", $player->getLevel()->getName());
				$this->plugin->getConfig()->save();
				unset($this->plugin->setWarzone[$player->getName()]);
				Messages::send($player, "chat", "Second position of the warzone area has been selected.");
				Messages::send($player, "chat", "The warzone area's positions are now set.");
			}
		}
		if($block->getId() == Block::CHEST) {
			if($this->plugin->isCommonCrate($block)) {
				if($player->getInventory()->contains(Item::get(Item::EMERALD, 101, 1))) {
					$this->plugin->openCrate($block, $player, "common");
				}else {
					$player->sendTip("§l§a» §f§rYou dont have a Common Key to open this crate. §l§a«");
				}
				$event->setCancelled();
			}
			if($this->plugin->isUncommonCrate($block)) {
				if($player->getInventory()->contains(Item::get(Item::EMERALD, 102, 1))) {
					$this->plugin->openCrate($block, $player, "uncommon");
				}else {
					$player->sendTip("§l§a» §f§rYou dont have an Uncommon Key to open this crate. §l§a«");
				}
				$event->setCancelled();
			}
			if($this->plugin->isLegendaryCrate($block)) {
				if($player->getInventory()->contains(Item::get(Item::EMERALD, 103, 1))) {
					$this->plugin->openCrate($block, $player, "legendary");
				}else {
					$player->sendTip("§l§a» §f§rYou dont have a Legendary Key to open this crate. §l§a«");
				}
				$event->setCancelled();
			}
			if($this->plugin->isVoteCrate($block)) {
				if($player->getInventory()->contains(Item::get(Item::EMERALD, 104, 1))) {
					$this->plugin->openCrate($block, $player, "vote");
				}else {
					$player->sendTip("§l§a» §f§rYou dont have a Vote Key to open this crate. §l§a«");
				}
				$event->setCancelled();
			}
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onUseFishingRod(PlayerUseFishingRodEvent $event) {
		$player = $event->getPlayer();
		if($this->plugin->isAtSpawn($player)) {
			$player->sendTip("§l§a» §r§fYou cannot use the Shooting Rod at spawn §l§a«");
			$event->setCancelled();
		}
		if($event->getAction() == PlayerUseFishingRodEvent::ACTION_START_FISHING && !$event->isCancelled()) {
			$event->setCancelled();
			$nbt = new CompoundTag("", [
				"Pos" => new ListTag("Pos", [
					new DoubleTag("", $player->x),
					new DoubleTag("", $player->y + $player->getEyeHeight()),
					new DoubleTag("", $player->z) 
				]),
				"Motion" => new ListTag("Motion", [
					new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
					new DoubleTag("", -sin($player->pitch / 180 * M_PI )),
					new DoubleTag("", cos($player->yaw / 180 * M_PI ) * cos($player->pitch / 180 * M_PI ))
				]),
				"Rotation" => new ListTag("Rotation", [
					new FloatTag("", $player->yaw),
					new FloatTag( "", $player->pitch)
				])
			]);
			$f = 2;
			$arrow = Entity::createEntity("Arrow", $player->chunk, $nbt, $player);
			$arrow->setMotion($arrow->getMotion()->multiply($f));
			$arrow->spawnToAll();
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onGameModeChange(PlayerGameModeChangeEvent $event) {
		$player = $event->getPlayer();
		if($event->getNewGamemode() != 0 && !$player->hasPermission("ec.limitations.gamemode")) {
			$event->setCancelled();
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onItemHeld(PlayerItemHeldEvent $event) {
		$item = $event->getItem();
		$player = $event->getPlayer();
		if($item->hasEnchantment(113)) {
			$player->addEffect(Effect::getEffect(Effect::STRENGTH)->setDuration(119000)->setVisible(false)->setAmplifier($item->getEnchantment(113)->getLevel()));
			Messages::send($player, "ce", "Demonforge Activated!");
		}else {
			if($player->hasEffect(Effect::STRENGTH) && $player->getEffect(Effect::STRENGTH)->getDuration() > 120000) {
				Messages::send($player, "ce", "Demonforge Deactivated!");
			}
		}
		if($item->getId() == Item::EMERALD && in_array($item->getDamage(), [101, 102, 103, 104])) {
			$name = [
				101 => "§l§a» §r§eCommon Key §l§a«",
				102 => "§l§a» §r§6Uncommon Key §l§a«",
				103 => "§l§a» §r§3Legendary Key §l§a«",
				104 => "§l§a» §r§bVote Key §l§a«"
			];
			if($item->getCustomName() !== "§r" . $name[$item->getDamage()]) {
				$item->setCustomName("§r" . $name[$item->getDamage()]);
				$player->getInventory()->setItemInHand($item);
			}
		}
		if($item->getId() == Item::FISHING_ROD) {
			$item->setCustomName("§r§l§a» §r§fShooting Rod §l§a«");
			$player->getInventory()->setItemInHand($item);
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onServerCommand(ServerCommandEvent $event) {
		$commandName = explode(" ", $event->getCommand())[0];
		if($commandName == "stop") {
			$levels = $this->plugin->getServer()->getLevels();
			foreach($levels as $level) {
				$level->save(true);
				$this->plugin->getLogger()->info("Saving level \"{$level->getName()}\"...");
				foreach($level->getEntities() as $entity) {
					if($entity instanceof Player) continue;
					$entity->saveNBT();
					$this->plugin->getLogger()->info("Saving entity \"{$entity->getName()}\"...");
				}
				foreach($level->getTiles() as $tile) {
					$tile->saveNBT();
					$this->plugin->getLogger()->info("Saving tile \"{$tile->getName()}\"...");
				}
				foreach($level->getPlayers() as $player) {
					$player->save();
					$this->plugin->getLogger()->info("Saving player data for \"{$player->getName()}\"...");
				}
			}
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event) {
		$player = $event->getPlayer();
		$message = $event->getMessage();
		$commandName = explode(" ", strtolower($event->getMessage()))[0];
		if(empty($message{0}) || $message{0} != "/") {
			return true;
		}
		$name = strtolower($player->getName());
		$dir = $this->plugin->getDataFolder() . "cmdlog/" . $name{0} . "/";
		@mkdir($dir);
		$log = new Config($dir . "$name.txt", Config::ENUM);
		$log->set("[" . date("m/d/Y h:i:s") . "]: " . $event->getMessage());
		$log->save();
		$rank = $this->plugin->purePerms->getUserDataMgr()->getGroup($player)->getName();
		$exemptedRanks = ["MainOwner", "Owner", "CoOwner", "Manager"];
		$blockedCommands = ["/me", "/op", "/plugins", "/givecoins", "/setcoins"];
		if(($commandName === "/god" || $commandName === "/gmc") && $this->plugin->isAtWarzone($player) && $player->isOp()) {
			$event->setCancelled();
			Messages::send($player, "warning", "You cannot use $commandName in the spawn.");
		}
		if(!in_array($rank, $exemptedRanks) && in_array($commandName, $blockedCommands)) {
			$event->setCancelled();
			Messages::send($player, "warning", "That command is disabled.");
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		CustomEnchantment::updateGlobal($player);
		if(!($player->isCreative() || $player->getAllowFlight() || $player->isSpectator()) && !$event->isCancelled()) {
			if(round($event->getTo()->getY() - $event->getFrom()->getY(), 3) == 0.375 && $player->isFlying()) {
				$this->plugin->flyCheck[$player->getName()]++;
			}else {
				$this->plugin->flyCheck[$player->getName()]--;
			}
			if($this->plugin->flyCheck[$player->getName()] == 60) {
				Messages::broadcast("chat", "{$player->getName()} has been kicked for using fly hacks.");
				$player->kick("You have been kicked for using fly hacks.", false);
			}
		}
		if($this->plugin->getFxSession($player) !== null) {
			$this->plugin->applyFxParticles($player);
		}
	}
	
	/**
	 * @ignoreCancelled true
	 *
	 * @priority HIGHEST
	 */
	public function onDeath(PlayerDeathEvent $event) {
		$player = $event->getPlayer();
		$cause = $player->getLastDamageCause();
		StatsManager::updatePlayer($player, "deaths");
		 if($cause instanceof EntityDamageByEntityEvent) {
			$killer = $cause->getDamager();
			if(!$killer instanceof Player) {
				return true;
			}
			$this->plugin->economyAPI->addMoney($killer, 100);
			$killer->sendTip("§l§a» §r§f+100 §2Coins §a§l«");
			StatsManager::updatePlayer($killer, "kills");
			if($this->plugin->hasBounty($player)) {
				$killer->sendMessage("§9You have killed §1{$player->getName()} §9and got the bounty of §1" . $this->plugin->economyAPI->getMonetaryUnit() . " " . $this->plugin->getBounty($player) . "§9.");
				$this->plugin->economyAPI->addMoney($killer, $this->plugin->getBounty($player));
				$this->plugin->removeBounty($player);
			}
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onDrop(PlayerDropItemEvent $event) {
		$player = $event->getPlayer();
		if($player->isCreative() && !$event->isCancelled()) {
			$event->setCancelled();
			Messages::send($player, "warning", "You cannot drop items while in creative mode.");
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();
		if($entity instanceof Player) {
			if($event->getCause() == EntityDamageEvent::CAUSE_FALL && $this->plugin->isAtWarzone($entity)) {
				$event->setCancelled();
			}
			if($entity->isSurvival()) {
				$this->plugin->updateHealthBar($entity);
			}
		}
		if($entity instanceof Player) {
			if($entity->getMaxHealth() > 40) {
				$entity->setMaxHealth(20);
			}
		}
		if($entity instanceof Player && $event->getDamage() >= $entity->getHealth() && !$entity->isCreative()) {
			$entity->setLastDamageCause($event);
			$event->setCancelled();
			$ev = new PlayerDeathEvent($entity, $entity->getInventory()->getContents(), "");
			$entity->setHealth(20);
			$entity->setMaxHealth(20);
			$entity->setOnFire(0);
			$entity->removeAllEffects();
			$entity->scheduleUpdate();
			$entity->teleport($entity->getSpawn());
			if($entity->getLevel()->getServer()->expEnabled) {
				$exp = mt_rand($entity->getDropExpMin(), $entity->getDropExpMax());
				if($exp > 0) $entity->getLevel()->spawnXPOrb($entity, $exp);
			}
			$ev->setKeepInventory($entity->getServer()->keepInventory);
			$ev->setKeepExperience($entity->getServer()->keepExperience);
			$entity->getServer()->getPluginManager()->callEvent($ev);
			if(!$ev->getKeepInventory()) {
				foreach($ev->getDrops() as $item) {
					$entity->getLevel()->dropItem($entity, $item);
				}
				$entity->getInventory()->clearAll();
			}
			if($entity->getServer()->expEnabled and !$ev->getKeepExperience()) {
				$exp = min(91, $entity->getTotalXp());
				$entity->getLevel()->spawnXPOrb($entity->add(0, 0.2, 0), $exp);
				$entity->setTotalXp(0, true);
			}
			$entity->setMotion($entity->normalize()->multiply($entity->distance($entity->getSpawn())));
			$entity->sendMessage("§c§l» §r§fYou died!");
		}
		if($event instanceof EntityDamageByEntityEvent) {
			$damager = $event->getDamager();
			$victim = $event->getEntity();
			if($damager instanceof Player) {
				if(isset($this->ride[$damager->getName()]) && $victim instanceof Player) {
					$damager->linkEntity($victim);
					$event->setCancelled();
					unset($this->ride[$damager->getName()]);
				}
				$blindness = Effect::getEffect(15)->setVisible(false);
				$confusion = Effect::getEffect(9)->setVisible(false);
				$poison = Effect::getEffect(19)->setVisible(false)->setAmplifier(1);
				$weakness = Effect::getEffect(18)->setVisible(false)->setAmplifier(1);
				$slowness = Effect::getEffect(2)->setVisible(false)->setAmplifier(2);
				$haste = Effect::getEffect(3)->setVisible(false)->setAmplifier(2);
				$speed = Effect::getEffect(1)->setVisible(false)->setAmplifier(1);
				$wither = Effect::getEffect(20)->setVisible(false)->setAmplifier(1);
				$strength = Effect::getEffect(5)->setVisible(false)->setAmplifier(1);
				if($damager->isCreative() || $damager->speed->y > 0 || $this->plugin->essentialsPE->getAPI()->isGod($damager)) {
					$event->setCancelled();
				}
				if($event->isCancelled()) {
					return false;
				}
				foreach($damager->getInventory()->getItemInHand()->getEnchantments() as $ench) {
					switch($ench->getId()) {
						case 13:
							$victim->setOnFire($ench->getLevel() * 4);
						break;
						case 22:
							$damager->getInventory()->addItem(Item::ARROW, 0, 1);
						break;
						case 100:
							$blindness->setDuration(200 + 10 * $ench->getLevel());
							$victim->addEffect($blindness);
						break;
						case 105:
							$confusion->setDuration(200 + 10 * $ench->getLevel());
							$victim->addEffect($confusion);
						break;
						case 115:
							$victim->setPosition($victim->add($victim->x * $ench->getLevel(), $victim->y, $victim->z - $ench->getLevel()));
						break;
						case 106:
							for($i = 1; $i < $ench->getLevel(); $i++) {
								$victim->getLevel()->spawnLightning(new Vector3($victim->x, $victim->y, $victim->z));
							}
							$victim->setHealth($victim->getHealth() - mt_rand(3, 6));
						break;
						case 107:
							$poison->setDuration(200 + $ench->getLevel() * 20);
							$victim->addEffect($poison);
						break;
						case 108:
							$weakness->setDuration(200 + $ench->getLevel() * 20);
							$slowness->setDuration(200 + $ench->getLevel() * 20);
							$victim->addEffect($weakness);
							$victim->addEffect($slowness);
						break;
						case 116:
							$stealedHP = $ench->getLevel() + 1;
							$damager->setHealth($damager->getHealth() + $stealedHP);
							$victim->setHealth($victim->getHealth() - $stealedHP);
						break;
						case 110:
							$event->setDamage($event->getDamage() * 2);
						break;
						case 114:
							$haste->setDuration(200 + 10 * $ench->getLevel());
							$speed->setDuration(200 + 10 * $ench->getLevel());
							$victim->addEffect($haste);
							$victim->addEffect($speed);
						break;
						case 117:
							if(!$victim instanceof Player) {
								return false;
							}
							if($this->plugin->chance(87)) {
								$victim->getInventory()->removeItem($victim->getInventory()->getHelmet());
							}
							if($this->plugin->chance(10)) {
								$victim->getInventory()->removeItem($victim->getInventory()->getChestplate());
							}
							if($this->plugin->chance(43)) {
								$victim->getInventory()->removeItem($victim->getInventory()->getLeggings());
							}
							if($this->plugin->chance(47)) {
								$victim->getInventory()->removeItem($victim->getInventory()->getBoots());
							}
						break;
						case 118:
							$event->setKnockBack($event->getKnockBack() + $ench->getLevel());
						break;
						case 119:
							$wither->setDuration(200 + 10 * $ench->getLevel());
							$victim->addEffect($wither);
						break;
						case 121:
							for($r = 0; $r <= 1080; $r++) {
								$a = cos(deg2rad($r / 3)) * 1.5;
								$b = sin(deg2rad($r / 3))* 1.5;
								$pos = new Vector3($victim->x + $a, $victim->y + 0.8, $victim->z + $b);
								$victim->setPosition($pos);
							}
						break;
						case 123:
							$hh = 16;
							for($s = 1; $s <= 3600; $s++) {
								$a = cos(deg2rad($s / 2)) * 1.5;
								$b = sin(deg2rad($s / 2)) * 1.5;
								$pos = new Vector3($victim->x + $a, $victim->y + ($s / $hh), $victim->z + $b);
								$victim->setPosition($pos);
							}
						break;
					}
				}
			}
		}
		if(!$event->isCancelled() && $entity instanceof Living) {
			if($event->getDamage() < 3) {
				$color = "§a";
			}elseif($event->getDamage() < 6) {
				$color = "§e";
			}else {
				$color = "§c";
			}
			$pos = $entity->add(0.1 * mt_rand (1, 9) * mt_rand (-1, 1), 0.1 * mt_rand (5, 9), 0.1 * mt_rand (1, 9) * mt_rand(-1, 1));
			$pos = $entity->add(0, 2.5, 0);
			$damageParticle = new FloatingTextParticle($pos, "", $color . "-" . $event->getDamage());
			$entity->getLevel()->addParticle($damageParticle, $entity->getLevel()->getPlayers());
			$this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new RemoveParticleTask($this->plugin, $damageParticle, $entity->getLevel()), 35);
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onSignChange(SignChangeEvent $event) {
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$text = [$event->getLine(0), $event->getLine(1), $event->getLine(2), $event->getLine(3)];
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onChat(PlayerChatEvent $event) {
		$player = $event->getPlayer();
		$name = strtolower($player->getName());
		$dir = $this->plugin->getDataFolder() . "chatlog/" . $name{0} . "/";
		@mkdir($dir);
		$log = new Config($dir . "$name.txt", Config::ENUM);
		$log->set("[" . date("m/d/Y h:i:s") . "]: " . $event->getMessage());
		$log->save();
		if(!$this->plugin->filter->check($event->getPlayer(), $event->getMessage())) {
			$event->setCancelled(true);
		}
		if(strpos($event->getMessage(), "@") !== false) {
			preg_match_all("#@([\\d\\w]+)#", $event->getMessage(), $users);
			$users = array_unique($users[1]);
			foreach($users as $user) {
				if(($pl = $this->plugin->getServer()->getPlayerExact($user)) !== null) {
					$pl->sendTip("§l§a» §r§b{$player->getName()} mentioned you in the chat! §l§a«");
					$pl->sendMessage("§a{$player->getName()}§7: §9" . $event->getMessage());
					$event->setCancelled();
				}
			}
		}
		if(!$event->isCancelled() && !$this->plugin->essentialsPE->getAPI()->isVanished($player)) {
			$pos = $player->add(0, 0.5, 0);
			$message = $event->getMessage();
			$particle = new FloatingTextParticle($pos, "", $player->getDisplayName() . "§r§7: §a" . $message);
			$player->getLevel()->addParticle($particle, $player->getLevel()->getPlayers());
			$this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new RemoveParticleTask($this->plugin, $particle, $player->getLevel()), 40);
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onPlace(BlockPlaceEvent $event) {
		$block = $event->getBlock();
		$item = $event->getItem();
		$player = $event->getPlayer();
		if(($this->plugin->isAtSpawn($player) || $this->plugin->isAtWarzone($player)) && !$player->hasPermission("ec.limitations.build")) {
			$event->setCancelled();
		}
		if($item->getId() == Block::MONSTER_SPAWNER) {
			$meta = $item->getDamage();
			if(isset($this->plugin->spawners[$meta])) {
				$event->setCancelled();
				$level = $block->getLevel();
				$level->setBlock($block, Block::get(Block::MONSTER_SPAWNER, $meta), true, true);
				$nbt = new CompoundTag("", [
					new StringTag("id", Tile::MOB_SPAWNER),
					new IntTag("x", $block->x),
					new IntTag("y", $block->y),
					new IntTag("z", $block->z),
					new IntTag("EntityId", $this->plugin->spawners[$meta]["id"])
				]);
				$tile = Tile::createTile("MobSpawner", $level->getChunk($block->getX() >> 4, $block->getZ() >> 4), $nbt);
				$player->getInventory()->remove(Item::get($item->getId(), 1, $meta));
			}
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onBreak(BlockBreakEvent $event) {
		$block = $event->getBlock();
		$item = $event->getItem();
		$player = $event->getPlayer();
		if($block->getId() == Block::MONSTER_SPAWNER) {
			if(in_array($item->getId(), [Item::IRON_PICKAXE, Item::DIAMOND_PICKAXE])) {
				if($item->hasEnchantment(Enchantment::TYPE_MINING_SILK_TOUCH)) {
					if(($tile = $level->getTile($block)) instanceof MobSpawner) {
						$tile->close();
						$event->setDrops([$block->getId(), 1, $block->getDamage()]);
					}
				}
			}
		}
		if(isset($this->plugin->xray[$player->getName()])){
			$this->plugin->revertDisplay($this->plugin->xray[$player->getName()], $player);
			unset($this->plugin->xray[$player->getName()]);
			$event->setCancelled();
			$player->sendMessage("§a§l» §r§fDisabling xray...");
		}
		if(($this->plugin->isAtSpawn($player) || $this->plugin->isAtWarzone($player)) && !$player->hasPermission("ec.limitations.build")) {
			$event->setCancelled();
		}
		if($this->plugin->chance(75) && !$event->isCancelled()) {
			$event->setCancelled();
			Messages::broadcast("chat", $player->getName() . " found a Common Loot Crate!");
			$this->plugin->placeLootCrate(new Position($block->x, $block->y, $block->z, $player->getLevel()), "common");
		}elseif($this->plugin->chance(43) && !$event->isCancelled()) {
			$event->setCancelled();
			Messages::broadcast("chat", $player->getName() . " found a Legendary Loot Crate!");
			$this->plugin->placeLootCrate(new Position($block->x, $block->y, $block->z, $player->getLevel()), "legendary");
		}elseif($this->plugin->chance(10) && !$event->isCancelled()) {
			$event->setCancelled();
			Messages::broadcast("chat", $player->getName() . " found a Rare Loot Crate!");
			$this->plugin->placeLootCrate(new Position($block->x, $block->y, $block->z, $player->getLevel()), "rare");
		}
		foreach($item->getEnchantments() as $ench) {
			if($ench->getId() == 120 && !$event->isCancelled()) {
				if($ench->getLevel() > 1) {
					$quantity = rand(2, $ench->getLevel());
				}else {
					$quantity = 2;
				}
				$blocks = [14, 15, 16, 21, 56, 73, 74, 129, 157];
				$drops = [14 => 266, 15 => 265, 16 => 263, 21 => 351, 56 => 264, 73 => 331, 74 => 331, 129 => 388, 157 => 406];
				if(!in_array($block->getId(), $blocks)) {
					return false;
				}
				if($block->getId() == 21) {
					$player->getLevel()->dropItem(new Vector3($block->x, $block->y, $block->z), Item::get(351, 4, $quantity));
				}else {
					$player->getLevel()->dropItem(new Vector3($block->x, $block->y, $block->z), Item::get($drops[$block->getId()], 0, $quantity));
				}
			}
		}
	}
	
	/**
	 * @ignoreCancelled true
	 *
	 * @priority HIGHEST
	 */
	public function onChunkLoad(ChunkLoadEvent $event) {
		if(Calendar::getSeason() == "Winter") {
			for($x = 0; $x < 16; ++$x) {
				for($z = 0; $z < 16; ++$z) {
					$event->getChunk()->setBiomeId($x, $z, Biome::ICE_PLAINS);
				}
			}
		}
		if(Calendar::getSeason() == "Autumn") {
			for($x = 0; $x < 16; ++$x) {
				for($z = 0; $z < 16; ++$z) {
					$event->getChunk()->setBiomeId($x, $z, Biome::DESERT);
				}
			}
		}
		if(Calendar::getSeason() == "Summer") {
			for($x = 0; $x < 16; ++$x) {
				for($z = 0; $z < 16; ++$z) {
					$event->getChunk()->setBiomeId($x, $z, Biome::HELL);
				}
			}
		}
		if(Calendar::getSeason() == "Spring") {
			for($x = 0; $x < 16; ++$x) {
				for($z = 0; $z < 16; ++$z) {
					$event->getChunk()->setBiomeId($x, $z, Biome::FOREST);
				}
			}
		}
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onPreLogin(PlayerPreLoginEvent $event) {
		$player = $event->getPlayer();
		if($this->checkProxy($player->getAddress())) {
			$player->kick("§cVPN Client Detected§r\n§2Please disable your VPN first before joining the server.", false);
			$this->plugin->getLogger()->notice("{$player->getName()} caught using a proxy / vpn client.");
			return true;
		}
		$this->plugin->getLogger()->info("{$player->getName()} passed the checks!");
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onRegainHealth(EntityRegainHealthEvent $event) {
		if(!($player = $event->getEntity()) instanceof Player) {
			return false;
		}
		if($player->getHealth() > $player->getMaxHealth()) {
			$player->setMaxHealth(20);
		}
		$this->plugin->updateHealthBar($player);
		$this->plugin->addRegenParticle($player);
	}
	
	/**
	 * @ignoreCancelled false
	 *
	 * @priority HIGHEST
	 */
	public function onCombust(EntityCombustEvent $event) {
		if(!($player = $event->getEntity()) instanceof Player) {
			return false;
		}
		$player->setMaxHealth(20);
		foreach($player->getInventory()->getArmorContents() as $armors) {
			$this->plugin->updateOverload($player, $armors);
		}
	}
	
	public function checkProxy($ip) {
		$contactEmail = "email@website.com";
		$timeout = 5;
		$banOnProability = 0.99;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_URL, "http://check.getipintel.net/check.php?ip=$ip&contact=$contactEmail");
		$response = curl_exec($ch);
		curl_close($ch);
		if($response > $banOnProability) {
			return true;
		}else {
			if($response < 0 || strcmp($response, "") == 0) {
				return false;
			}
			return false;
		}
	}
	
}