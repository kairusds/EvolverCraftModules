<?php

namespace modules\entity\pets;

class ChickenPet extends Pet {

	const NETWORK_ID = 10;

	public $width = 0.6;
	public $length = 0.6;
	public $height = 1.8;
	
	public function getName() {
		return "Chicken Pet";
	}
	

}
