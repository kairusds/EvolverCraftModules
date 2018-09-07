<?php

namespace modules\entity\pets;

class BatPet extends Pet {

	const NETWORK_ID = 19;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 0.6;

	public function getName() {
		return "Bat Pet";
	}
	
}