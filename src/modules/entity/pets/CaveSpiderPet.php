<?php

namespace modules\entity\pets;

class CaveSpiderPet extends Pet {

	const NETWORK_ID = 40;

	public $width = 1;
	public $length = 1;
	public $height = 0.5;

	public function getName() {
		return "Cave Spider Pet";
	}

}
