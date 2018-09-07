<?php

namespace modules\entity\pets;

/**
 * PigZombie AKA Zombie Pigman (Zombie Wombat in UK)
 */
class PigZombiePet extends Pet {
	
	const NETWORK_ID = 36;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
	
	public function getName() {
		return "Pig Zombie Pet";
	}
	
}