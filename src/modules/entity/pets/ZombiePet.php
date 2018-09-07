<?php

namespace modules\entity\pets;

class ZombiePet extends Pet {
	
	const NETWORK_ID = 32;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
	
	public function getName() {
		return "Zombie Pet";
	}
	
}