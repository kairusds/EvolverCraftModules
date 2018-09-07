<?php

namespace modules\entity\pets;

class BlazePet extends Pet {
	
	const NETWORK_ID = 43;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 1.8;
	
	public function getName() {
		return "Blaze Pet";
	}

}