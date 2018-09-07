<?php

namespace modules\entity\pets;

class SpiderPet extends Pet {
	
	const NETWORK_ID = 35;
	
	public $width = 0.3;
	public $length = 0.9;
	public $height = 1.9;
	
	public function getName() {
		return "Spider Pet";
	}
	
}