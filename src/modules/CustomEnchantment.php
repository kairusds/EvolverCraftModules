<?php

namespace modules;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\entity\Effect;
use pocketmine\Player;

class CustomEnchantment {
	
	private $item;
	
	const BLINDNESS = 100;
	const GLOWING = 101;
	const OBSIDIAN_SHIELD = 102;
	const GEARS = 103;
	const SPRINGS = 104;
	const CONFUSION = 105;
	const LIGHTNING = 106;
	const POISON = 107;
	const FROZEN = 108;
	const AQUATIC = 109;
	const DOUBLE_DAMAGE = 110;
	const OVERLOAD = 111;
	const DEMONFORGE = 113;
	const FEATHERWEIGHT = 114;
	const DISAPPEARER = 115;
	const LIFESTEAL = 116;
	const DISARMOR = 117;
	const OBLITERATION = 118;
	const WITHER = 119;
	const VEINGLORY = 120;
	const WHIRLPOOL = 121;
	const NUTRITION = 122;
	const TORNADO = 123;
	const ARMOURED = 124;
	const EXPLOSIVE = 125;
	const LIGHT_ARROWS = 126;
	
	public function __construct(Item $item) {
		$this->item = $item;
	}
	
	public function add($e, $levelE = null) {
		if($this->item->hasCustomName()) {
			$cn = $this->item->getCustomName();
		}else {
			$cn = $this->item->getName();
		}
		$enchant = Enchantment::getEnchantment($e);
		if($enchant->getId() == -1) return;
		if(isset($levelE)) {
			$maxL = Main::getEnchantMaxLevel($enchant->getId());
			if($levelE >= $maxL) {
				$levelE = $maxL;
			}
			$enchant->setLevel($levelE);
		} else {
			if($e !== 111) $enchant->setLevel(1);
		}
		$level = $enchant->getLevel();
		$l = self::integerToRoman($level);
		if($e === 100) {
			$name = "Blindness";
		}elseif($e === 101) {
			$name = "Glowing";
		}elseif($e === 102) {
			$name = "Obsidian Shield";
		}elseif($e === 103) {
			$name = "Gears";
		}elseif($e === 104) {
			$name = "Springs";
		}elseif($e === 105) {
			$name = "Confusion";
		}elseif($e === 106) {
			$name = "Lightning";
		}elseif($e === 107) {
			$name = "Poison";
		}elseif($e === 108) {
			$name = "Frozen";
		}elseif($e === 109) {
			$name = "Aquatic";
		}elseif($e === 110) {
			$name = "Double Damage";
		}elseif($e === 111) {
			$name = "§cOverload";
		}elseif($e === 113) {
			$name = "Demonforge";
		}elseif($e === 114) {
			$name = "Featherweight";
		}elseif($e === 115) {
			$name = "Disappearer";
		}elseif($e === 116) {
			$name = "Lifesteal";
		}elseif($e === 117) {
			$name = "Disarmor";
		}elseif($e === 118) {
			$name = "Obliteration";
		}elseif($e === 119) {
			$name = "Wither";
		}elseif($e === 120) {
			$name = "Vein Glory";
		}elseif($e === 121) {
			$name = "§bWhirlpool";
		}elseif($e === 122) {
			$name = "Nutrition";
		}elseif($e === 123) {
			$name = "§6Tornado";
		}elseif($e === 124) {
			$name = "Armoured";
		}elseif($e === 125) {
			$name = "§cExplosive";
		}elseif($e === 126) {
			$name = "§bLightning Arrows";
		}
		if($e > 99) {
			$this->item->setCustomName($cn . "§r\n§7" . $name . " §r§7" . $l);
		}
		$this->item->addEnchantment($enchant);
	}
	
	public static function integerToRoman(int $integer) {
		$integer = intval($integer);
		$result = ""; 
		$lookup = ["M" => 1000, "CM" => 900, "D" => 500, "CD" => 400, "C" => 100, "XC" => 90, "L" => 50, "XL" => 40, "X" => 10, "IX" => 9, "V" => 5, "IV" => 4, "I" => 1];
		foreach($lookup as $roman => $value) {
			$matches = intval($integer / $value);
			$result .= str_repeat($roman, $matches);
			$integer = $integer % $value;
		}
		return $result;
	}
	
	public function nutrition($level, Player $player) {
		$basic = 0.0025;
		$multiply = $level === 5 ? 4.4 : $level;
		$add = $basic + $multiply * $basic;
		$p->setFood($p->getFood() + $add);
	}
	
	public static function updateGlobal(Player $player) {
		if($player->getInventory()->getHelmet()->hasEnchantment(122)) {
			$this->nutrition($player->getInventory()->getHelmet()->getEnchantment(122)->getLevel(), $p);
		}elseif($player->getInventory()->getChestplate()->hasEnchantment(122)) {
			$this->nutrition($player->getInventory()->getChestplate()->getEnchantment(122)->getLevel(), $p);
		}elseif($player->getInventory()->getLeggings()->hasEnchantment(122)) {
			$this->nutrition($player->getInventory()->getLeggings()->getEnchantment(122)->getLevel(), $p);
		}elseif($player->getInventory()->getBoots()->hasEnchantment(122)) {
			$this->nutrition($player->getInventory()->getBoots()->getEnchantment(122)->getLevel(), $p);
		}
		if($player->getInventory()->getHelmet()->hasEnchantment(124)) {
			if($player->getHealth() <= 7) $player->addEffect(Effect::getEffect(11)->setDuration(80)->setVisible(false)->setAmplifier(1));
		}elseif($player->getInventory()->getChestplate()->hasEnchantment(124)) {
			if($player->getHealth() <= 7) $player->addEffect(Effect::getEffect(11)->setDuration(80)->setVisible(false)->setAmplifier(1));
		}elseif($player->getInventory()->getLeggings()->hasEnchantment(124)) {
			if($player->getHealth() <= 7) $player->addEffect(Effect::getEffect(11)->setDuration(80)->setVisible(false)->setAmplifier(1));
		}elseif($player->getInventory()->getBoots()->hasEnchantment(124)) {
			if($player->getHealth() <= 7) $player->addEffect(Effect::getEffect(11)->setDuration(80)->setVisible(false)->setAmplifier(1));
		}
	}
}