<?php

namespace modules\entity\pets;

class SnowGolemPet extends Pet {
	
	const NETWORK_ID = 21;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 1.8;
	
	public function getName() {
		return "Snow Golem Pet";
	}
	
}