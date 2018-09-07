<?php

namespace modules\entity\pets;

class SlimePet extends Pet {
	
	const NETWORK_ID = 37;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 5;
	
	public function getName() {
		return "Slime Pet";
	}
	
}