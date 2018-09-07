<?php

namespace modules\entity\pets;

class IronGolemPet extends Pet {
	
	const NETWORK_ID = 20;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 2.8;
	
	public function getName() {
		return "Iron Golem Pet";
	}
	
}