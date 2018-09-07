<?php

namespace modules\entity\pets;

class PigPet extends Pet {
	
	const NETWORK_ID = 12;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 1.9;
	
	public function getName() {
		return "Pig Pet";
	}
	
}