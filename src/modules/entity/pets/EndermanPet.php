<?php

namespace modules\entity\pets;

class EndermanPet extends Pet {

	const NETWORK_ID = 38;

	public $width = 0.3;
	public $length = 0.9;
	public $height = 1.8;

	public function getName() {
		return "Enderman Pet";
	}

}
