<?php

namespace modules;

use onebone\economyapi\EconomyAPI;
use pocketmine\block\Block;
use pocketmine\level\Location;
use modules\entity\{
	FloatingText
};
use pocketmine\entity\Entity;
use pocketmine\inventory\ChestInventory;
use pocketmine\command\{
	Command,
	CommandSender,
	ConsoleCommandSender
};
use pocketmine\plugin\PluginBase;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use modules\task\{
	ItemCheckerTask,
	ChatFilterTask,
	PlayerCheckTask,
	WelcomeTask,
	DropPartyTask,
	DropItemsTask
};
use pocketmine\level\particle\HeartParticle;
use pocketmine\math\{
	Vector2,
	Vector3
};
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\level\Position;
use pocketmine\{
	Player,
	Server
};
use pocketmine\nbt\tag\{
	CompoundTag,
	ListTag,
	DoubleTag,
	FloatTag,
	StringTag,
	IntTag
};
use pocketmine\utils\{
	Config,
	Utils
};
use pocketmine\network\protocol\{
	BlockEventPacket,
	UpdateBlockPacket
};
use modules\entity\{
	FlyingTNT,
	LightArrow,
	TeleportProjectile
};
use pocketmine\event\TranslationContainer;

class Main extends PluginBase {
	
	private static $instance = null;
	
	public $trackerHUD = [],
	$setWarzone = [],
	$setSpawn = [],
	$items = [],
	$commands = [],
	$queue = [],
	$lists = [],
	$fxSessions = [],
	$flyCheck = [],
	$dropStatus = 0,
	$dropTime = 0,
	$dropDuration = 0,
	$definedCrates = [],
	$ride = [],
	$particles,
	$setCrate = [],
	$xray = [],
	$ops,
	$blockBreaker,
	$spawners = [],
	$blockedItems = [],
	$loadTime,
	$topFacsHolo = [],
	$welcomeHolo = [],
	$pvHandler;
	
	public static function getInstance() {
		return self::$instance;
	}
	
	public function onLoad() {
		self::$instance = $this;
		$this->loadTime = time();
	}
	
	public function onEnable() {
		$da = $this->getDataFolder();
		foreach([
			$da,
			$da . "vaults/",
			$da . "Lists/",
			$da . "players/",
			$da . "worlds/",
			$da . "chatlog/",
			$da . "cmdlog/",
		] as $dir) {
			if(!is_dir($dir)) {
				mkdir($dir, 0777, true);
			}
		}
		Calendar::setTimezone("Asia/Tokyo");
		$this->saveDefaultConfig();
		$this->saveResource("crates.yml");
		$this->saveResource("boosters.yml");
		$this->saveResource("ops.txt");
		$this->loadVoteReward();
		$this->loadWelcomeHologram();
		$this->loadTopFactionsHolo();
		$this->pvHandler = new PrivateVaults($this);
		$this->blockedItems = self::parseBlockedItems($this->getConfig()->get("blocked-items"));
		$this->spawners = self::parseSpawnersList($this->getConfig()->get("spawners"));
		$this->particles = new Particles($this);
		$this->essentialsPE = $this->getServer()->getPluginManager()->getPlugin("EssentialsPE");
		$this->purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
		$this->pureChat = $this->getServer()->getPluginManager()->getPlugin("PureChat");
		$this->filter = new ChatFilter();
		$this->crates = new Config($this->getDataFolder() . "crates.yml", Config::YAML);
		$this->dropTime = $this->getConfig()->get("drop-party")["starts-in"];
		$this->dropDuration = $this->getConfig()->get("drop-party")["duration"];
		$this->ops = new Config($this->getDataFolder() . "ops.txt");
		$this->ops->save();
		for($i = 100; $i < 127; $i++) {
			Enchantment::$enchantments[$i] = new Enchantment($i, "", Enchantment::RARITY_MYTHIC, Enchantment::ACTIVATION_EQUIP, Enchantment::SLOT_NONE);
		}
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->getServer()->getPluginManager()->registerEvents(new Booster($this), $this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new ItemCheckerTask($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new PlayerCheckTask($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new ChatFilterTask($this), 30);
		if($this->getConfig()->get("drop-party")["enabled"]) {
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new DropPartyTask($this), 20 * 60);
			$this->getServer()->getScheduler()->scheduleRepeatingTask(new DropItemsTask($this), 20);
		}
		StatsManager::init();
		foreach($this->getConfig()->get("worlds-load-on-enable") as $world) {
			$this->getServer()->loadLevel($world);
		}
		if($this->getConfig()->get("backup-on-enable")) {
			foreach($this->getServer()->getLevels() as $level) {
				$tar = new \PharData($this->getDataFolder() . "worlds/" . $level->getFolderName() . ".tar");
				$tar->startBuffering();
				$tar->buildFromDirectory(realpath($sender->getServer()->getDataPath() . "worlds/" . $level->getFolderName()));
				$tar->compress(\Phar::GZ);
				$tar->stopBuffering();
				@unlink($this->getDataFolder() . "worlds/" . $level->getFolderName() . ".tar");
				$this->getLogger()->info("§a$level->getName()} has been backed up!");
			}
		}
		if(($loadTime = round(time() - $this->loadTime)) > 60) {
			$this->getLogger()->warning("Core load time is more than 60 seconds, remove some plugins/useless files to reduce the core's load time.");
		}else {
			$this->getLogger()->notice("Done! Loaded the core within $loadTime " . ($loadTime > 1 ? "seconds" : "second"));
		}
	}
	
	public function onDisable() {
		if(!$this->getServer()->isRunning()) {
			return;
		}
		$this->getServer()->shutdown();
	}
	
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		switch(strtolower($cmd->getName())) {
			case "givetpc":
				if(isset($args[0])) {
					$name = $args[0];
					$target = $this->getServer()->getPlayer($name);
					if($target !== null) {
						$customName = "§6~~ §bTPCompass §6~~";
						$item = Item::get(Item::COMPASS, 0, 1);
						$item->setCustomName($customName);
						$item->namedtag->CompassEnergy = new IntTag("CompassEnergy", 300);
						$target->getInventory()->addItem($item);
						$sender->sendMessage(new TranslationContainer("%commands.give.success", [
							$item->getName() . " (" . $item->getId() . ":" . $item->getDamage() . ")",
							(string) $item->getCount(),
							$target->getName()
						]));
						return true;
					}else {
						$sender->sendMessage(new TranslationContainer("§c%commands.generic.player.notFound"));
						return true;
					}
				}else {
					$sender->sendMessage("§9Usage§7: §b/givetpc <player>");
					return true;
				}
				break;
			case "booster":
				if(isset($args[0])) {
					if($args[0] == "buy") {
						
					}
					return true;
				}
				break;
			case "sortinv":
				$inv = $sender->getInventory();
				$save = [];
				foreach($inv->getContents() as $item) {
					$save[] = $item->getId() . ":" . $item->getDamage() . ":" . $item->getCount();
				}
				asort($save);
				$sort = [];
				foreach($save as $ii) {
					$i = explode(":", $ii);
					$sort[] = Item::get($i[0], $i[1], $i[2]);
				}
				$inv->setContents($sort);
				$player->getInventory()->sendContents($player);
				Messages::send($sender, "Done! You're inventory has been sorted.");
				break;
			case "seeinv":
				if(isset($args[0]) && !is_numeric($args[0])) {
					$player = $this->getServer()->getPlayer($args[0]);
					if($player == null) {
						$sender->sendMessage(new TranslationContainer("§c%commands.generic.player.notFound"));
						return true;
					}
					$items = $sender->getInventory()->getContents();
					$cItems = array_chunk($items, 8); 
					$max = count($cItems);
					if(!isset($args[1]) || !is_numeric($args[1]) || $args[1] <= 0) {
						$page = 1;
					}elseif($args[1] > $max) {
						$page = $max;
					}else {
						$page = $args[1];
					}
					$sender->sendMessage("§7---------- §bInventory Observer (page $page/$max) §7----------");
					foreach($cItems[$page - 1] as $item) {
						$sender->sendMessage("§7- §a" . $item->getName() . " " . $item->getId() . ":" . $item->getDamage() . " * ". $item->getCount());
					}
					return true;
				}else {
					$items = $sender->getInventory()->getContents();
					$cItems = array_chunk($items, 8); 
					$max = count($cItems);
					if(!isset($args[0]) || !is_numeric($args[0]) || $args[0] <= 0) {
						$page = 1;
					}elseif($args[0] > $max) {
						$page = $max;
					}else {
						$page = $args[0];
					}
					$sender->sendMessage("§7---------- §bInventory Observer (page $page/$max) §7----------");
					foreach($cItems[$page - 1] as $item) {
						$sender->sendMessage("§7- §a" . $item->getName() . " " . $item->getId() . ":" . $item->getDamage() . " * ". $item->getCount());
					}
					return true;
				}
				break;
			case "xray":
				if(isset($this->xray[$sender->getName()])) {
					$sender->sendMessage("§c§l» §r§fXray is already enabled.");
					return true;
				}else {
					$sender->sendMessage("§a§l» §r§fSearching for ores...");
					$this->xray[$sender->getName()] = $this->fetchBlocks($sender);
					$this->renderBlocks($this->xray[$sender->getName()], $sender);
					$sender->sendMessage("§a§l» §r§fXray enabled, tap on a block to disable xray.");
					return true;
				}
				break;
			case "setcrate":
				if(isset($this->setCrate[$sender->getName()])) {
					$sender->sendMessage("§a§l» §r§fYou are already in the state of defining a crate.");
					return true;
				}
				if(!isset($args[0]) || !in_array($args[0], ["common", "uncommon", "legendary", "vote"])) {
					$sender->sendMessage("§l§a» §r§fAvailable Types of Crates: §bcommon, uncommon, legendary, vote§f.");
					return true;
				}
				$this->setCrate[$sender->getName()] = $args[0];
				$sender->sendMessage("§l§a» §r§fYou are now defining a $args[0] crate, tap a chest and it will get defined!");
				break;
			case "stats":
				$player = null;
				if(!isset($args[0])) {
					$player = $sender;
				}
				if(isset($args[0]) && StatsManager::playerExists(Server::getInstance()->getOfflinePlayer($args[0]))) {
					$player = $this->getServer()->getOfflinePlayer($args[0]);
				}elseif(isset($args[0]) && !StatsManager::playerExists(Server::getInstance()->getOfflinePlayer($args[0]))) {
					$player = $sender;
				}
				$sender->sendMessage(($player instanceof Player ? StatsManager::getStats($player) : "You cant use this command in the console!"));
				break;
			case "topkills":
				$sender->sendMessage(StatsManager::getTopKills());
				break;
			case "backup":
				if(!isset($args[0]) || !is_dir($this->getServer()->getDataPath() . "worlds/" . $args[0])) {
					$sender->sendMessage("§9Usage§7: §b/backup <world>");
					return true;
				}
				$tar = new \PharData($this->getDataFolder() . "worlds/" . $args[0] . ".tar");
				$tar->startBuffering();
				$tar->buildFromDirectory(realpath($this->getServer()->getDataPath() . "worlds/" . $args[0]));
				$tar->compress(\Phar::GZ);
				$tar->stopBuffering();
				@unlink($this->getDataFolder() . "worlds/" . $args[0] . ".tar");
				$sender->sendMessage("§9" . $args[0] . " has been backed up!");
				break;
			case "restore":
				if($sender instanceof Player) {
					$sender->sendMessage("§l§c» §r§fCommand must be ran in the console.");
					return true;
				}
				if(!isset($args[0]) || !is_dir($this->getServer()->getDataPath() . "worlds/" . $args[0])) {
					$sender->sendMessage("§9Usage§7: §b/restore <world>");
					return true;
				}
				if(!file_exists($this->getDataFolder() . "worlds/" . $args[0] . ".tar.gz")) {
					$sender->sendMessage("§9The backup of that world does not exist.");
					return true;
				}
				$level = $this->getServer()->getLevelByName($args[0]);
				if($level == $this->getServer()->getDefaultLevel()) {
					foreach($level->getPlayers() as $player) {
						$player->kick("§cWorld is being restored! Join back again after 3 minutes.", false);
					}
				}else {
					foreach($level->getPlayers() as $player) {
						$player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
						$player->sendMessage("§9Teleporting to the default world...");
					}
				}
				$this->getServer()->unloadLevel($level);
				$tar = new \PharData($this->getDataFolder() . "worlds/" . $args[0] . ".tar.gz");
				$tar->extractTo($this->getServer()->getDataPath() . "worlds/" . $args[0], null, true);
				$this->getServer()->loadLevel($args[0]);
				$sender->sendMessage("§9The world is now restored!");
				break;
			case "ride":
				if(isset($this->ride[$sender->getName()])) {
					$sender->sendMessage("§9You are already selecting a player to ride!");
					return true;
				}
				$sender->sendMessage("§9Now tap a player and you will ride it!");
				$this->ride[$sender->getName()] = true;
				break;
			case "addbounty":
				if(count($args) < 2 || !is_numeric($args[1])) {
					$sender->sendMessage("§9Usage§7: §b/addbounty <player> <amount>");
					return true;
				}
				if(($player = $this->getServer()->getPlayer()) == null) {
					$sender->sendMessage("§9That player is not online.");
					return true;
				}
				if($player->getName() == $sender->getName()) {
					$sender->sendMessage("§9You can't add a bounty to yourself!");
					return true;
				}
				if($this->addBounty($player, $args[1]) == 4 && $this->hasBounty($player)) {
					$sender->sendMessage("§9That player already has a bounty set.");
					return true;
				}
				if($this->addBounty($player, $args[1]) == 0) {
					Messages::broadcast("chat", "A bounty of §1" . $this->economyAPI->getMonetaryUnit() . " " . $args[1] . " has been added to §1{$player->getName()} §9and is issued by §1{$sender->getName()}§9.");
					$this->bounty[$player->getName()] = true;
					return true;
				}elseif($this->addBounty($player, $args[1]) == 1) {
					$sender->sendMessage("§9The bounty must not be higher than your current coins.");
					return true;
				}elseif($this->addBounty($player, $args[1]) == 2) {
					$sender->sendMessage("§9The bounty must not be higher than §1" . $this->economyAPI->getMonetaryUnit() . " " . $this->getConfig()->get("max-bounty-price") . "§9.");
					return true;
				}
				break;
			case "checkbounty":
				if(($player = $this->getServer()->getPlayer()) == null) {
					$sender->sendMessage("§9That player is not online.");
					return true;
				}
				if($this->getBounty($player) == 0) {
					$sender->sendMessage("§1{$player->getName()} §9does not have a bounty.");
					return true;
				}elseif($this->getBounty($player) > 0) {
					$sender->sendMessage("§1{$player->getName()} §9has a bounty of §1" . $this->economyAPI->getMonetaryUnit() . " " . $this->getBounty($player) . "§9.");
					return true;
				}
				break;
			case "vote":
				if(isset($args[0]) && !in_array($args[0], ["help", "tutorial", "where", "rewards"])) {
					Messages::send($sender, "warning", "Unknown parameter. Try /vote help for the list of the parameters");
					return true;
				}
				if(isset($args[0]) && strtolower($args[0]) == "help") {
					$sender->sendMessage("§7---------- §bVote Help Menu §7----------");
					$sender->sendMessage("§6/vote§7: §9claim your reward for voting on our vote links");
					$sender->sendMessage("§6/vote tutorial§7: §9see the voting tutorial");
					$sender->sendMessage("§6/vote where§7: §9see our voting links");
					$sender->sendMessage("§6/vote rewards§7: §9see the rewards for voting on our voting links");
					return true;
				}
				if(isset($args[0]) && strtolower($args[0]) == "tutorial") {
					$sender->sendMessage("§7---------- §bVoting Tutorial §7----------");
					$sender->sendMessage("§61. §9Visit our voting links with §1/vote where§9.");
					$sender->sendMessage("§62. §9Enter your in game username on the field box(case sensitive).");
					$sender->sendMessage("§63. §9Solve the CAPTCHA.");
					$sender->sendMessage("§64. §9Click the \"Vote\" button.");
					$sender->sendMessage("§65. §9Return to the server and type §1/vote §9to claim your reward then walla!");
					return true;
				}
				if(isset($args[0]) && strtolower($args[0]) == "where") {
					$sender->sendMessage("§7---------- §bVoting Links §7----------");
					$sender->sendMessage("§6- §9bit.ly/evolvedvote");
					return true;
				}
				if(isset($args[0]) && strtolower($args[0]) == "rewards") {
					$sender->sendMessage("§7---------- §bVote Rewards §7----------");
					$sender->sendMessage("§6- §9Color chat permission");
					$sender->sendMessage("§6- §9+5000 Coins");
					$sender->sendMessage("§6- §9Wraith Sword §b[§aConfusion I§b]");
					$sender->sendMessage("§6- §9Diamond Sword §b[§aSharpness III§b]");
					$sender->sendMessage("§6- §9Diamond Pickaxe §b[§aEfficiency III§b]");
					$sender->sendMessage("§6- §9Diamond Armor Set §b[§aProtection II§b]");
					$sender->sendMessage("§6- §9Vote Chest x3");
					$sender->sendMessage("§6- §9§o...and 9 more minor items.");
					$sender->sendMessage("§6- §9Remember: Our rewards gets doubled when its weekend!"); 
					return true;
				}
				if(!isset($args[0])) {
					if(in_array(strtolower($sender->getName()), $this->queue)) {
						Messages::send($sender, "warning", "Relax buddy! We are already checking your vote status.");
						return true;
					}
					$this->queue[] = strtolower($sender->getName());
					$requests = [];
					foreach($this->lists as $list) {
						if(isset($list["check"]) && isset($list["claim"])) {
							$requests[] = new ServerListQuery($list["check"], $list["claim"]);
						}
						$query = new RequestThread(strtolower($sender->getName()), $requests);
						$this->getServer()->getScheduler()->scheduleAsyncTask($query);
					}
				}
				break;
			case "setspawn":
				if(isset($this->setSpawn[$sender->getName()])) {
					Messages::send($sender, "warning", "You are already setting the spawn area!");
					return true;
				}
				$this->setSpawn[$sender->getName()] = "";
				Messages::send($sender, "chat", "Select 2 positions to set the spawn area's positions.");
				break;
			case "setwarzone":
				if(isset($this->setWarzone[$sender->getName()])) {
					Messages::send($sender, "warning", "You are already setting the warzone area!");
					return true;
				}
				$this->setWarzone[$sender->getName()] = "";
				Messages::send($sender, "chat", "Select 2 positions to set the warzone area's positions.");
				break;
			case "expstats":
				if(!$sender instanceof Player) {
					$sender->sendMessage("§cRun this command in-game.");
					return true;
				}
				$this->sendExpStats($sender);
				break;
			case "redeemexp":
				if(!$sender instanceof Player) {
					$sender->sendMessage("§cRun this command in-game.");
					return true;
				}
				if(!isset($args[0])) {
					$sender->sendMessage("§cUsage: §b/redeemexp <exp>");
					return true;
				}
				if(is_numeric($args[0]) && $args[0] > 0) {
					$this->redeemExp($sender, $args[0]);
					return true;
				}else {
					Messages::send($sender, "warning", "You have provided an invalid amount of your experience!");
					return true;
				}
				break;
			case "fx":
				if(!isset($args[0])) {
					$sender->sendMessage("§9Usage§7: §b/fx <set|off|type|list>");
					return true;
				}
				if($args[0] == "set") {
					if(!is_null($this->particles->getParticleByName($args[1], $sender))) {
						if($this->getFxSession($sender) == null) {
							$this->addFxSession(new FxSession($sender, "circular", $this->particles->getParticleByName($args[1], $sender)));
						}else {
							$this->getFxSession($sender)->setParticle($this->particles->getParticleByName($args[1], $sender));
						}
						$sender->sendMessage("§9Your particle fx has been set to §1" . $args[1] . "§9.");
						return true;
					}else {
						$sender->sendMessage("§9Invalid fx particle. To see the list of all particle fx, use /fx list.");
						return true;
					}
				}
				if($args[0] == "off") {
					if($this->getFxSession($sender) == null) {
						$sender->sendMessage("§9You are not using a particle fx.");
						return true;
					}
					$sender->sendMessage("§9Your particle fx has been turned off.");
					$this->getFxSession($sender)->close();
					return true;
				}
				if($args[0] == "type") {
					if($this->getFxSession($sender) == null) {
						$sender->sendMessage("§9You must be using a particle fx before changing its type, in order to do so type §1/fx set§9.");
						return true;
					}
					if(isset($args[1]) && in_array($args[1], ["spiral", "circular"])) {
						$this->getFxSession($sender)->setType($args[1]);
						$sender->sendMessage("§9Your particle fx type has been set to §1" . $args[1] . "§9.");
						return true;
					}else {
						$sender->sendMessage("§9Usage§7: §b/fx type <spiral|circular>");
						return true;
					}
				}
				if($args[0] == "list") {
					$sender->sendMessage("§7---------- §bParticle Fx List  §7----------");
					$sender->sendMessage(implode("§a, §f", $this->particles->getAll()));
					return true;
				}
				break;
		}
	}
	
	public function spawnWh(Player $player) {
		foreach($this->welcomeHolo as $holo) {
			$holo->spawnTo($player);
		}
	}
	
	public function spawnTf(Player $player) {
		foreach($this->topFacsHolo as $holo) {
			$holo->spawnTo($player);
		}
	}
	
	public function despawnWh(Player $player) {
		foreach($this->welcomeHolo as $holo) {
			$holo->despawnFrom($player);
		}
	}
	
	public function despawnTf(Player $player) {
		foreach($this->topFacsHolo as $holo) {
			$holo->despawnFrom($player);
		}
	}
	
	public function loadWelcomeHologram() {
		$this->welcome = new Config($this->getDataFolder() . "welcome.properties", Config::PROPERTIES, [
			"x" => 0,
			"y" => 0,
			"z" => 0,
			"line1" => "§bWelcome to §aEvolved§2PE",
			"line2" => "§bYou are playing on §1Factions",
			"line3" => "§bShop: §1bit.ly/evolvedstore",
			"line4" => "§bFollow us on Twitter §1@EvolvedCraftPE"
		]);
		$level = $this->getServer()->getDefaultLevel();
		$holoData = $this->welcome->getAll();
		$level->loadChunk($holoData["x"], $holoData["z"], true);
		$positions = [
			new Position($holoData["x"], $holoData["y"] + 1.7, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 1.4, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 1.1, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 0.8, $holoData["z"], $level)
		];
		$particles = [
			new FloatingText($positions[0], $holoData["line1"]),
			new FloatingText($positions[1], $holoData["line2"]),
			new FloatingText($positions[2], $holoData["line3"]),
			new FloatingText($positions[3], $holoData["line4"])
		];
		$this->welcomeHolo = $particles;
	}
	
	public function dispatchCommand($command) {
		$this->getServer()->dispatchCommand(new ConsoleCommandSender(), $command);
	}
	
	public function alignStringCenter($string, $string2) {
		$length = strlen($string);
		$half = $length / 4;
		$str = $string . "§r\n" . str_repeat(" ", $half) . $string2;
		return $str;
	}
	
	public function getHealthBar(Player $player) {
		$nametag = $this->pureChat->getNametag($player);
		$health = "§4§l{$player->getHealth()} §cHP";
		$str = $this->alignStringCenter($nametag, $health);
		return $str;
	}
	
	public function updateHealthBar(Player $player) {
		$player->setNameTag($this->getHealthBar($player));
	}
	
	public function redeemExp(Player $player, $exp) {
		$currentExp = $player->getTotalXp();
		if($exp > 32000) {
			Message::send($player, "warning", "You cannot redeem more than 32000 EXP.");
			return false;
		}
		if($currentExp >= $exp) {
			$player->setTotalXp($player->getTotalXp() - $exp);
			$xpBottle = Item::get(Item::ENCHANTING_BOTTLE, $exp, 1);
			$xpBottle->setCustomName("§a§lExperience Bottle §r§2{$exp} EXP §7(Tap)§r\n§7Enchanter: {$player->getName()}");
			$player->getInventory()->addItem($xpBottle);
			Message::send($player, "chat", "You have successfully redeemed {$exp} for an Experience Bottle.");
		}else {
			Message::send($player, "warning", "You don't have enough experience to redeem an Experience Bottle.");
		}
	}
	
	public function sendExpStats(Player $player) {
		$difference = $player::getLevelXpRequirement($player->getXpLevel()) - $player->getFilledXp();
		Message::send($player, "chat", "You have a total of " . $player->getFilledXp() . " EXP " . ($player->getXpLevel() > 0 ? "Level: " . $player->getXpLevel() : ""));
		Message::send($player, "chat", "You need " . $difference . " EXP to reach Level " . ($player->getXpLevel() + 1));
	}

	public function loadVoteReward() {
		if(!is_file($this->getDataFolder() . "vote.yml")) {
			file_put_contents($this->getDataFolder() . "vote.yml", $this->getResource("vote.yml"));
		}
		$this->voteConfig = new Config($this->getDataFolder() . "vote.yml", Config::YAML);
		$this->commands = $this->voteConfig->get("commands");
		foreach(scandir($this->getDataFolder() . "Lists/") as $file) {
			$ext = explode(".", $file);
			$ext = (count($ext) > 1 && isset($ext[count($ext) - 1]) ? strtolower($ext[count($ext) - 1]) : "");
			if($ext == "vrc") {
				$this->lists[] = json_decode(file_get_contents($this->getDataFolder() . "Lists/$file"), true);
			}
			foreach($this->voteConfig->get("items") as $item) {
				$r = explode(":", $item);
				$this->items[] = new Item($r[0], $r[1], $r[2]);
			}
		}
	}
	
	public function updateOverload(Player $player, Item $item) {
		if(($maxHealth = $player->getMaxHealth()) > 40) {
			return false;
		}
		if($item->hasEnchantment(111)) {
			if($item->isHelmet()) {
				$player->setMaxHealth($health + 4);
				$player->setHealth($player->getHealth());
			}elseif($item->isChestplate()) {
				$player->setMaxHealth($health + 8);
				$player->setHealth($player->getHealth());
			}elseif($item->isLeggings()) {
				$player->setMaxHealth($health + 6);
				$player->setHealth($player->getHealth());
			}elseif($item->isBoots()) {
				$player->setMaxHealth($health + 2);
				$player->setHealth($player->getHealth());
			}
		}
	}
	
	public function isAtSpawn(Player $player) {
		$x1 = $this->getConfig()->get("spawnX1");
		$z1 = $this->getConfig()->get("spawnZ1");
		$x2 = $this->getConfig()->get("spawnX2");
		$z1 = $this->getConfig()->get("spawnZ2");
		if($player->getX() >= $x1 && $player->getX() <= $x2 && $player->getZ() >= $z1 && $player->getZ() <= $z2 && $player->getLevel()->getName() == $this->getConfig()->get("spawnLevel")) {
			return true;
		}
		return false;
	}
	
	public function isAtWarzone(Player $player) {
		$x1 = $this->getConfig()->get("warzoneX1");
		$z1 = $this->getConfig()->get("warzoneZ1");
		$x2 = $this->getConfig()->get("warzoneX2");
		$z1 = $this->getConfig()->get("warzoneZ2");
		if($player->getX() >= $x1 && $player->getX() <= $x2 && $player->getZ() >= $z1 && $player->getZ() <= $z2 && $player->getLevel()->getName() == $this->getConfig()->get("warzoneLevel")) {
			return true;
		}
		return false;
	}
	
	public function applyFxParticles(Player $player) {
		$session = $this->getFxSession($player);
		if($session !== null) {
			if($session->getType() == "spiral") {
				$center = $player->add(0.5, 0, 0.5);
				$radius = 0.5;
				$count = 100;
				$particle = $session->getParticle();
				$particle->add(0.5, 0, 0.5);
				for($yaw = 0, $y = $center->y; $y < $center->y + 2; $yaw += (M_PI * 2) / 20, $y += 1 / 20) {
					$x = -sin($yaw) + $center->x;
					$z = cos($yaw) + $center->z;
					$particle->setComponents($x, $y, $z);
					$player->getLevel()->addParticle($particle);
				}
			}elseif($session->getType() == "circular") {
				for($r = 0; $r <= 800; $r++) {
					$a = cos(deg2rad($r / 3)) * 0.8;
					$b = sin(deg2rad($r / 3))* 0.8;
					$session->getParticle()->setComponents($player->x + $a + 0.5, $player->y + 0.8, $player->z + $b + 0.5);
					$player->getLevel()->addParticle($session->getParticle(), $player->getLevel()->getPlayers());
				}
			}
		}
	}
	
	public function placeLootCrate(Position $pos, $type = "common") {
		$level = $pos->getLevel();
		$x = $pos->x;
		$y = $pos->y;
		$z = $pos->z;
		if($type == "common") {
			$chest = Block::get(Block::CHEST, 0, new Position($x, $y, $z, $level));
			$chest->place(Item::get(Item::CHEST), $chest, $chest, 0, 0, 0, 0);
			$chest->setDamage(0);
			$level->setBlock($chest, $chest);
			$tile = $chest->getLevel()->getTile($pos);
			$inventory = $tile->getInventory();
			if($tile instanceof Chest) {
				$tile->setName("§a§l>> §2Common Loot Crate §a<<");
				$inventory->addItem(Item::get(Item::LEATHER_CAP, 0, 1));
				$inventory->addItem(Item::get(Item::LEATHER_TUNIC, 0, 1));
				$inventory->addItem(Item::get(Item::LEATHER_PANTS, 0, 1));
				$inventory->addItem(Item::get(Item::LEATHER_BOOTS, 0, 1));
				$inventory->addItem(Item::get(Item::WOODEN_SWORD, 0, 1));
				$inventory->addItem(Item::get(Item::WOODEN_SHOVEL, 0, 1));
				$inventory->addItem(Item::get(Item::WOODEN_PICKAXE, 0, 1));
				$inventory->addItem(Item::get(Item::WOODEN_AXE, 0, 1));
				$inventory->addItem(Item::get(Item::WOODEN_HOE, 0, 1));
				$inventory->addItem(Item::get(Item::APPLE, 0, rand(2, 4)));
				$inventory->addItem(Item::get(Item::GLASS, 0, 28));
				$inventory->addItem(Item::get(Item::STICK, 0, 4));
				$inventory->addItem(Item::get(Item::PLANKS, 0, rand(10, 20)));
				$inventory->addItem(Item::get(Item::STONE, 0, rand(10, 20)));
				$inventory->setSize(14);
				$tile->saveNBT();
			}
			for($r = 0; $r <= 1080; $r++) {
				$a = cos(deg2rad($r / 3)) * 0.6;
				$b = sin(deg2rad($r / 3)) * 0.6;
				$pos = new Vector3($x + $a, $y + 0.7, $z + $b);
				$particle = $this->particles->getParticleByName("redstone", $pos);
				$level->addParticle($particle);
			}
		}
		if($type == "legendary") {
			$chest = Block::get(Block::CHEST, 0, new Position($x, $y, $z, $level));
			$chest->place(Item::get(Item::CHEST), $chest, $chest, 0, 0, 0, 0);
			$chest->setDamage(0);
			$level->setBlock($chest, $chest);
			$tile = $chest->getLevel()->getTile($pos);
			$inventory = $tile->getInventory();
			if($tile instanceof Chest) {
				$tile->setName("§a§l>> §6Legendary Loot Crate §a<<");
				$inventory->addItem(Item::get(Item::CHAIN_HELMET, 0, 1));
				$inventory->addItem(Item::get(Item::IRON_CHESTPLATE, 0, 1));
				$inventory->addItem(Item::get(Item::CHAIN_LEGGINGS, 0, 1));
				$inventory->addItem(Item::get(Item::CHAIN_BOOTS, 0, 1));
				$inventory->addItem(Item::get(Item::IRON_SWORD, 0, 1));
				$inventory->addItem(Item::get(Item::IRON_SHOVEL, 0, 1));
				$inventory->addItem(Item::get(Item::IRON_PICKAXE, 0, 1));
				$inventory->addItem(Item::get(Item::IRON_AXE, 0, 1));
				$inventory->addItem(Item::get(Item::IRON_HOE, 0, 1));
				$inventory->addItem(Item::get(Item::BOW, 0, 1));
				$inventory->addItem(Item::get(Item::ARROW, 0, 16));
				$inventory->addItem(Item::get(Item::GOLDEN_APPLE, 0, 5));
				$inventory->addItem(Item::get(Item::GOLD_INGOT, 0, 5));
				$inventory->addItem(Item::get(Item::PLANKS, 0, rand(15, 30)));
				$inventory->addItem(Item::get(Item::STONE, 0, rand(15, 30)));
				$inventory->setSize(15);
				$tile->saveNBT();
			}
			for($r = 0; $r <= 1080; $r++) {
				$a = cos(deg2rad($r / 3)) * 0.6;
				$b = sin(deg2rad($r / 3)) * 0.6;
				$pos = new Vector3($x + $a, $y + 0.7, $z + $b);
				$particle = $this->particles->getParticleByName("redstone", $pos);
				$level->addParticle($particle);
			}
		}
		if($type == "rare") {
			$chest = Block::get(Block::CHEST, 0, new Position($x, $y, $z, $level));
			$chest->place(Item::get(Item::CHEST), $chest, $chest, 0, 0, 0, 0);
			$chest->setDamage(0);
			$level->setBlock($chest, $chest);
			$tile = $chest->getLevel()->getTile($pos);
			$inventory = $tile->getInventory();
			if($tile instanceof Chest) {
				$tile->setName("§a§l>> §bRare Loot Crate §a<<");
				$inventory->addItem(Item::get(Item::DIAMOND_HELMET, 0, 1));
				$inventory->addItem(Item::get(Item::DIAMOND_CHESTPLATE, 0, 1));
				$inventory->addItem(Item::get(Item::DIAMOND_LEGGINGS, 0, 1));
				$inventory->addItem(Item::get(Item::DIAMOND_BOOTS, 0, 1));
				$iSword = Item::get(Item::IRON_SWORD, 0, 1);
				$ce = new CustomEnchantment($iSword);
				$ce->add(rand(106, 109));
				$inventory->addItem($iSword);
				$inventory->addItem(Item::get(Item::DIAMOND_SHOVEL, 0, 1));
				$inventory->addItem(Item::get(Item::DIAMOND_PICKAXE, 0, 1));
				$inventory->addItem(Item::get(Item::DIAMOND_AXE, 0, 1));
				$inventory->addItem(Item::get(Item::DIAMOND_HOE, 0, 1));
				$inventory->addItem(Item::get(Item::BOW, 0, 1));
				$inventory->addItem(Item::get(Item::ARROW, 0, 16));
				$inventory->addItem(Item::get(Item::GOLDEN_APPLE, rand(0, 1), 1));
				$inventory->addItem(Item::get(Item::GOLD_INGOT, 0, rand(2, 10)));
				$inventory->addItem(Item::get(Item::CAMERA, 0, 1));
				$inventory->addItem(Item::get(Item::COOKED_PORKCHOP, 0, rand(3, 12)));
				$inventory->addItem(Item::get(Item::PLANKS, 0, rand(30, 50)));
				$inventory->addItem(Item::get(Item::STONE, 0, rand(30, 50)));
				$inventory->setSize(17);
				$tile->saveNBT();
			}
			for($r = 0; $r <= 1080; $r++) {
				$a = cos(deg2rad($r / 3)) * 0.6;
				$b = sin(deg2rad($r / 3)) * 0.6;
				$pos = new Vector3($x + $a, $y + 0.7, $z + $b);
				$particle = $this->particles->getParticleByName("redstone", $pos);
				$level->addParticle($particle);
			}
		}
	}
	
	public function chance($percent) {
		return mt_rand(1, 100) == $percent;
	}
	
	public function isEnchantment(int $id) {
		if($id > -1 && $id < 25) {
			return true;
		}
		return false;
	}
	
	public function isCustomEnchantment(int $id) {
		return in_array($id, [
			100,
			101,
			102,
			103,
			104,
			105,
			106,
			107,
			108,
			109,
			109,
			110,
			111,
			113,
			114,
			115,
			116,
			117,
			118,
			119,
			120,
			122,
			124,
			125,
			126
		]);
	}
	
	public function hasBounty(Player $player) {
		$bconfig = new Config($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".json", Config::JSON);
		if($bconfig->get("bounty") > 0) {
			return true;
		}
		return false;
	}
	
	public function addBountyConfig(Player $player) {
		if(!file_exists($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".json")) {
			$bconfig = new Config($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".json", Config::JSON, [
				"bounty" => 0,
				"issuer" => 0
			]);
		}
	}
	
	public function removeBounty(Player $player) {
		$bconfig = new Config($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".json", Config::JSON);
		$bconfig->set("bounty", 0);
		$bconfig->set("issuer", 0);
		$bconfig->save();
	}
	
	public function getBounty(Player $player) {
		$bconfig = new Config($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".json", Config::JSON);
		return $bconfig->get("bounty");
	}
	
	/**
	 * 0: Success
	 * 1: Money not enough
	 * 2: Amount is too high
	 */
	public function addBounty(Player $player, $amount) {
		if($this->hasBounty($player)) {
			if($amount < $this->getBounty($player)) {
				return 1;
			}
			if($amount > $this->getConfig()->get("max-bounty-price")) {
				return 2;
			}
			$bconfig = new Config($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".json", Config::JSON);
			if($bconfig->get("issuer") == 1) {
				return 4;
			}
			$bconfig->set("bounty", $amount);
			$bconfig->set("issuer", 1);
			$bconfig->save();
			return 0;
		}
	}
	
	public function addRegenParticle(Player $player) {
		for($i = 0; $i < 2; $i++) {
			$player->getLevel()->addParticle(new HeartParticle(new Vector3($player->getX() - 0.5 + mt_rand(1, 10) / 10, $player->getY() + 0.8, $player->getZ() - 0.5 + mt_rand(1, 10) / 10)));
		}
	}
	
	public function rewardPlayer(Player $player, $multiplier) {
		if($multiplier < 1) {
			Messages::send($player, "warning", "You haven't voted yet! In order to do so, type /vote tutorial to find out how.");
			return false;
		}
		$clones = [];
		foreach($this->items as $item) {
			$clones[] = clone $item;
		}
		foreach($clones as $item) {
			$item->setCount($item->getCount() * $multiplier);
			$player->getInventory()->addItem($item);
		}
		foreach($this->commands as $command) {
			$this->dispatchCommand(str_replace([
				"{player}",
				"{nickname}",
				"{x}",
				"{y}",
				"{y1}",
				"{z}"
			],
			[
				$player->getName(),
				$player->getDisplayName(),
				$player->getX(),
				$player->getY(),
				$player->getY() + 1,
				$player->getZ()
			], Utils::translateColors($command)));
		}
		if(!empty($this->voteConfig->get("message"))) {
			$message = str_replace([
				"{player}",
				"{nickname}"
			],
			[
				$player->getName(),
				$player->getDisplayName()
			], Utils::translateColors($this->voteConfig->get("message")));
			$this->getServer()->broadcastMessage($message);
		}
	}
	
	public function getFxSessions() {
		return $this->fxSessions;
	}
	
	public function addFxSession(FxSession $session) {
		$this->fxSessions[$session->getPlayer()->getName()] = $session;
	}
	
	public function getFxSession(Player $player) {
		if(isset($this->fxSessions[$player->getName()])) {
			return $this->fxSessions[$player->getName()];
		}
		return null;
	}
	
	public function loadTopFactionsHolo() {
		$this->topfacs = new Config($this->getDataFolder() . "topfacs.properties", Config::PROPERTIES, [
			"x" => 0,
			"y" => 0,
			"z" => 0,
			"level" => "world"
		]);
		$holoData = $this->topfacs->getAll();
		$level = $this->getServer()->getLevelByName($holoData["level"]);
		$level->loadChunk($holoData["x"], $holoData["z"], true);
		$positions = [
			new Position($holoData["x"], $holoData["y"] + 3.7, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 3.4, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 3.1, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 2.9, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 2.6, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 2.3, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 2, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 1.7, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 1.4, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 1.1, $holoData["z"], $level),
			new Position($holoData["x"], $holoData["y"] + 0.8, $holoData["z"], $level)
		];
		$particles = [];
		$particles[] = new FloatingText($positions[0], "§l§a»§8------- §3Top 10 Most Powerful Factions §8-------§a«");
		$topfacs = $this->getServer()->getPluginManager()->getPlugin("FactionsPro")->getTop10Factions();
		foreach($topfacs as $key => $val) {
			$name = $val["name"];
			$pow = $val["pow"];
			$players = $val["players"];
			$msg = "§6" . ($key + 1) . ".§e$name §6Power: §e$pow §f{§a$players/20}";
			$particles[] = new FloatingText($positions[$key], $msg);
		}
		$this->topFacsHolo = $particles;
	}
	
	public function openCrate(Position $pos, Player $player, $type) {
		$item = $this->getRandomCrateItem($type);
		$crate = new Crate($pos, $pos->level->getBlock($pos));
		$crate->open($player, $this->getRandomCrateItem($type));
	}
	
	private function getRandomCrateItem($type) {
		$config = $this->crates->getAll();
		$rand = mt_rand(0, count($config[$type . "-items"]) - 1);
		return $this->loadCrateItem($config[$type . "-items"][$rand]);
	}
	
	private function loadCrateItem($item) {
		$ite = explode(":", $item);
		$id = $ite[0];
		$damage = $ite[1];
		$count = $ite[2];
		if(isset($ite[3]) && $ite[3] !== "DEFAULT") {
			$name = $ite[3];
		}
		if(isset($ite[4])) {
			$enchantment = Enchantment::getEnchantmentByName($ite[4]);
		}
		if($ite[4] < 1 || !isset($ite[4])) {
			$enchantment->setLevel(1);
		}
		if(isset($ite[5])) {
			$enchantment->setLevel($ite[5]);
		}
		$it = new Item($ite[0], $ite[1], $ite[2], $ite[3]);
		if(isset($args[4])) {
			$it->addEnchantment($enchantment);
		}
		return $it;
	}
	
	public function enchantment2name($id) {
		return Enchantment::getEnchantment($id)->getName();
	}
	
	public function getParticles() {
		return $this->particles;
	}
	
	public function isNight($tick) {
		$totalhour = ($tick / 1000) + 6;
		$totalday = floor($totalhour / 24);
		$nowhour = floor((floor($totalhour) - $totalday * 24));
		return $nowhour >= 18 || $nowhour < 6;
	}
	
	public function getEconomy() {
		return EconomyAPI::getInstance();
	}
	
	public function fetchBlocks(Player $p) {
		$ret = [];
		$s = 16;
		for($x = $p->getX() - $s; $x <= $p->getX() + $s; $x++) {
			for($y = $p->getY() - $s; $y <= $p->getY() + $s; $y++) {
				for($z = $p->getZ() - $s; $z <= $p->getZ() + $s; $z++) {
					$block = $p->getLevel()->getBlock(new Vector3($x, $y, $z));
					if($block->getID() !== 0 || $y == $p->getY() - 1) {
						if(!$this->isOre($block)) {
							$ret[] = $block;
						}
					}
				}
			}
		}
		return $ret;
	}
	
	public static function parseBlockList(array $array = []) {
		$blocks = [];
		foreach($array as $data) {
			$temp = explode(",", str_replace(" ", "", $data));
			$blocks[$temp[0]] = $temp[1];
		}
		return $blocks;
	}

	public static function block2string(Block $block) {
		return $block->__toString() . "x:{$block->x}, y:{$block->y}, z:{$block->z}";
	}
	
	public static function getNearbyBlocks(Position $center, $size) {
		$result = [];
		for($x = $center->getX() - $size; $x <= $center->getX() + $size; $x++) {
			for($y = $center->getY() - $size; $x <= $center->getY() + $size; $y++) {
				for($z = $center->getZ() - $size; $z <= $center->getZ() + $size; $z++) {
					$block = $center->getLevel()->getBlock(new Vector3($x, $y, $z));
					if($block->getId() !== 0) {
						$result[] = $block;
					}
				}
			}
		}
		return $result;
	}
	
	public function renderBlocks(array $blocks, Player $p) {
		foreach($blocks as $block) {
			if($block->getY() !== ($p->getY() - 1)) {
				$pk = new UpdateBlockPacket();
				$pk->x = $block->getX();
				$pk->y = $block->getY();
				$pk->z = $block->getZ();
				$pk->block = 0;
				$pk->meta = 0;
				$p->dataPacket($pk);
			}
		}
	}
	
	public function revertDisplay(array $blocks, Player $p) {
		foreach($blocks as $block) {
			$pk = new UpdateBlockPacket();
			$pk->x = $block->getX();
			$pk->y = $block->getY();
			$pk->z = $block->getZ();
			$pk->block = $block->getID();
			$pk->meta = $block->getDamage();
			$p->dataPacket($pk);
		}
	}
	
	public function isOre(Block $block) {
		return in_array($block->getID(), [14, 15, 16, 21, 56, 73, 129, 153]);
	}
	
	public static function launchTnt(Player $player) {
		$nbt = new Compound("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $player->x),
				new DoubleTag("", $player->y + $player->getEyeHeight()),
				new DoubleTag("", $player->z)
			]),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
				new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
				new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
			]),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", $player->yaw),
				new FloatTag("", $player->pitch)
			]),
		]);		
		$f = 1.5;
		$entity = new FlyingTNT($player->chunk, $nbt, $player);
		$entity->shouldExplode = true;
		$entity->setMotion($entity->getMotion()->multiply($f));
		$entity->spawnToAll();
	}
	
	public static function launchLightArrow(Player $player) {
		self::launchEntity($player, "LightArrow");
	}
	
	public static function launchTeleport(Player $player) {
		self::launchEntity($player, "TeleportProjectile");
	}
	
	private static function launchEntity(Player $player, $className) {
		$nbt = new Compound("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $player->x),
				new DoubleTag("", $player->y + $player->getEyeHeight()),
				new DoubleTag("", $player->z)
			]),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("", -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
				new DoubleTag("", -sin($player->pitch / 180 * M_PI)),
				new DoubleTag("", cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
			]),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", $player->yaw),
				new FloatTag("", $player->pitch)
			]),
		]);
		$f = 1.5;
		$entity = new $className($player->chunk, $nbt, $player);
		$entity->setMotion($entity->getMotion()->multiply($f));
		$entity->spawnToAll();
	}
	
	public static function getEnchantMaxLevel(int $enchantmentId) {
		$maxLevel = 0;
		if(Enchantment::getEnchantMaxLevel($enchantmentId) > 5) {
			$maxLevel = 5;
		}elseif($enchantmentId == CustomEnchantment::OVERLOAD) {
			$maxLevel = 1;
		}else {
			$maxLevel = Enchantment::getEnchantMaxLevel($enchantmentId);
		}
		return $maxLevel;
	}
	
	public function getRandomString($length = 5) {
		$word = array_merge(range("a", "z"), range("A", "Z"));
		shuffle($word);
		return substr(implode($word), 0, $length);
	}
	
	public function seconds2clock($seconds) {
		return gmdate("h:i:s", $seconds);
	}
	
	public function mins2hours($mins) {
		return gmdate("h:i", $mins);
	}
	
	public function seconds2mins($seconds) {
		return gmdate("i:s", $seconds);
	}
	
	public static function parseSpawnersList(array $list) {
		$spawners = [];
		foreach($list as $data) {
			$temp = explode(",", str_replace(" ", "", $data));
			$meta = $temp[0];
			$spawners[$meta] = [
				"id" => $temp[1],
				"name" => $temp[2],
				"price" => $temp[3]
			];
		}
		return $spawners;
	}

	public function getSpawnerMetaById($id) {
		foreach($this->spawners as $meta => $val) {
			if($id == $val["id"]) {
				return $meta;
			}
		}
		return null;
	}
	
	public function getSpawnerPriceByMeta($meta) {
		$result = $this->spawners[$meta];
		if($result !== null) {
			return $result["price"];
		}
		return null;
	}
	
	public static function parseBlockedItems(array $list = []) {
		$result = [];
		foreach($list as $item) {
			$ite = Item::fromString($item);
			if($ite->getName() !== "Unknown") {
				$result[] = $ite->getId();
			}
		}
		return $result;
	}
	
	public static function getEntityNetworkId(string $name) {
		if(($class = self::getEntityClassByName($name)) !== null) {
			return constant($class . "::NETWORK_ID");
		}
		return null;
	}
	
	public static function getEntityClassByName(string $name) {
		$knownEntities = Entity::getKnownEntities();
		if(($class = $knownEntities[$name]) !== null) {
			return $class;
		}
		return null;
	}
	
}