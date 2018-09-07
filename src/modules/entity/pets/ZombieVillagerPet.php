<?php

namespace modules\entity\pets;

class ZombieVillagerPet extends Pet {
	
	const NETWORK_ID = 44;

	public $width = 1.031;
	public $length = 0.891;
	public $height = 2.125;
	
	public function getName() {
		return "Zombie Villager Pet";
	}
	
}