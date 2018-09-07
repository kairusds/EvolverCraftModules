<?php
 
namespace modules;

use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\{
	CompoundTag, ListTag, StringTag, IntTag, ByteTag
};
use pocketmine\inventory\ChestInventory;
use pocketmine\tile\{
	Tile, Chest
};
use pocketmine\level\Position;
use pocketmine\command\{
	Command, CommandSender, CommandExecutor
};
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\player\PlayerQuitEvent;

class PrivateVaults implements Listener, CommandExecutor {
	
	private $plugin;
	private $sessions = [];
	private $path;
	private $chest = [];
	
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
		$this->path = $plugin->getDataFolder() . "vaults/";
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
		$plugin->getCommand("pv")->setExecutor($this);
	}
	
	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		if(($session = $this->sessions[$player->getName()]) !== null) {
			unset($session);
			$this->savePv($player, $this->chest[strtolower($player->getName())]);
		}
	}
	
	public function hasPv($player) {
		if($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);
		return file_exists($this->path . "/$player.json");
	}
	
	public function createPv($player, $num) {
		if($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);
		$cfg = new Config($this->path . "/$player.json", Config::JSON);
		for ($i = 0; $i < 26; $i++) {
			$cfg->setNested("$num.items.$i", [0, 0, 0, []]);
		}
		$cfg->save();
	}
	
	public function getFakeChest(Position $position) {
		$nbt = new CompoundTag("", [
			new ListTag("Items", []),
			new StringTag("id", Tile::CHEST),
			new IntTag("x", $position->x),
			new IntTag("y", $position->y),
			new IntTag("z", $position->z)
		]);
		$nbt->Items->setTagType(NBT::TAG_Compound);
		$chest = new Chest($position->getLevel()->getChunk($position->getX() >> 4, $position->getZ() >> 4), $nbt);
		$chest->setName("§6Private Vault");
		return new ChestInventory($chest);
	}
	
	public function loadPv(Player $player, $num) {
		$chest = $this->getFakeChest($player);
		$name = strtolower($player->getName());
		$cfg = new Config($this->path . "/$name.json", Config::JSON);
		for ($i = 0; $i < 26; $i++) {
			$ite = $cfg->getNested("$num.items.$i");
			$item = Item::get($ite[0]);
			$item->setDamage($ite[1]);
			$item->setCount($ite[2]);
			foreach($ite[3] as $key => $en) {
				$enchantment = Enchantment::getEnchantment($en[0]);
				$enchantment->setLevel($en[1]);
				$item->addEnchantment($enchantment);
			}
			$chest->getInventory()->setItem($i, $item);
		}
		return $chest;
	}
	
	public function savePv($player, ChestInventory $inventory) {
		if($player instanceof Player) {
			$player = $player->getName();
		}
		$player = strtolower($player);
		$cfg = new Config($this->path . "/$name.json", Config::JSON);
		$name = $player;
		$holder = $inventory->getHolder();
		if(isset($this->sessions[$name])) {
			$cfg = new Config($this->path . "/$name.json", Config::JSON);
			for($i = 0; $i < 26; $i++) {
				$item = $inventory->getItem($i);
				$id = $item->getId();
				$damage = $item->getDamage();
				$count = $item->getCount();
				$enchantments = $item->getEnchantments();
				$ens = [];
				foreach($enchantments as $en) {
					$ide = $en->getId();
					$level = $en->getLevel();
					$ens[] = [$ide, $level];
				}
				$num = $this->sessions[strtolower($event->getPlayer()->getName())];
				$cfg->setNested("$num.items.$i", [$id, $damage, $count, $ens]);
				$cfg->save();
			}
			$holder->close();
			unset($this->chest[$player]);
		}
	}
	
	public function onInventoryClose(InventoryCloseEvent $event) {
		$inventory = $event->getInventory();
		$player = $event->getPlayer();
		$this->savePv($player, $inventory);
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if(!$sender instanceof Player) {
			$sender->sendMessage("Run this duck command in-game");
			return true;
		}
		if(!$this->hasPv($sender)) {
			$sender->sendmessage("§9Creating private vault data...");
			for($i = 1; $i < 51; $i++) {
				$this->createPv($sender, $i);
			}
			$sender->sendMessage("§9Private vault data created, run /pv again to open your private vault.");
			return true;
		}
		if(isset($args[0])) {
			if(!is_numeric($args[0]) || ($args[0] < 1 || $args[0] > 50)) {
				$sender->sendMessage("§9Usage§7: §b/pv [1-50]");
				return true;
			}
			$sender->addWindow($chest = $this->loadPv($sender, $args[0]));
			$this->sessions[strtolower($sender->getName())] = $args[0];
			$this->chest[strtolower($sender->getName())] = $chest;
		}else {
			$sender->addWindow($chest = $this->loadPv($sender, 1));
			$this->sessions[strtolower($sender->getName())] = 1;
			$this->chest[strtolower($sender->getName())] = $chest;
		}
	}
	
}