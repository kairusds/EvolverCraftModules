<?php

namespace modules;

use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\level\particle\{
	BubbleParticle,
	CriticalParticle,
	DustParticle,
	EnchantParticle,
	InstantEnchantParticle,
	ExplodeParticle,
	LargeExplodeParticle,
	HugeExplodeParticle,
	EntityFlameParticle,
	FlameParticle,
	HeartParticle,
	InkParticle,
	ItemBreakParticle,
	LavaDripParticle,
	LavaParticle,
	PortalParticle,
	RedstoneParticle,
	SmokeParticle,
	SplashParticle,
	SporeParticle,
	TerrainParticle,
	MobSpawnParticle,
	WaterDripParticle,
	WaterParticle,
	EnchantmentTableParticle,
	HappyVillagerParticle,
	AngryVillagerParticle,
	RainSplashParticle,
	DestroyBlockParticle,
	Particle
};
use modules\Main;

class Particles {
	
	private $plugin;
	
	public function __construct(Main $plugin) {
		$this->plugin = $plugin;
	}
	
	public function getParticleByName($name = "", Vector3 $pos = null, $data = null) {
		switch ($name) {
			case "explode":
				return new ExplodeParticle ( $pos);
			case "largeexplode":
				return new LargeExplodeParticle ( $pos);
			case "hugeexplode":
				return new HugeExplodeParticle ( $pos);
			case "bubble":
				return new BubbleParticle ( $pos);
			case "splash":
				return new SplashParticle ( $pos);
			case "water":
				return new WaterParticle ( $pos);
			case "crit":
			case "critical":
				return new CriticalParticle ( $pos);
			case "spell":
				return new EnchantParticle ( $pos);
			case "instantspell":
				return new InstantEnchantParticle ( $pos);
			case "smoke":
				return new SmokeParticle ( $pos, ($data === null ? 0: $data) );
			case "dripwater":
				return new WaterDripParticle ( $pos);
			case "driplava":
				return new LavaDripParticle ( $pos);
			case "townaura":
			case "spore":
				return new SporeParticle ( $pos);
			case "portal":
				return new PortalParticle ( $pos);
			case "entityflame":
				return new EntityFlameParticle ( $pos);
			case "flame":
				return new FlameParticle ( $pos);
			case "lava":
				return new LavaParticle ( $pos);
			case "reddust":
			case "redstone":
				return new RedstoneParticle ( $pos, ($data === null ? 1: $data) );
			case "snowballpoof":
			case "snowball":
				return new ItemBreakParticle ( $pos, Item::get ( Item::SNOWBALL ) );
			case "slime":
				return new ItemBreakParticle ( $pos, Item::get ( Item::SLIMEBALL ) );
			case "heart":
				return new HeartParticle ( $pos, ($data === null ? 0: $data) );
			case "ink":
				return new InkParticle ( $pos, ($data === null ? 0: $data) );
			case "enchantmenttable":
			case "enchantment":
				return new EnchantmentTableParticle ( $pos);
			case "happyvillager":
				return new HappyVillagerParticle ( $pos);
			case "angryvillager":
				return new AngryVillagerParticle ( $pos);
			case "droplet":
			case "rain":
				return new RainSplashParticle ( $pos);
			case "mobspawn":
				return new MobSpawnParticle ( $pos);
			case "colorful":
			case "colourful":
				return new DustParticle ( $pos, rand(0, 255), rand(0, 255), rand(0, 255));
		}
		return null;
	}
	
	public function getAll(): array {
		return array( 
				"bubble",
				"explode",
				"splash",
				"water",
				"critical",
				"spell",
				"smoke",
				"driplava",
				"dripwater",
				"spore",
				"portal",
				"flame",
				"entityflame",
				"lava",
				"reddust",
				"snowball",
				"heart",
				"ink",
				"hugeexplode",
				"largeexplode",
				"instantspell",
				"slime",
				"enchantment",
				"happyvillager",
				"angryvillager",
				"droplet",
				"mobspawn",
				"colorful"
		);
	}
}