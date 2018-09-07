<?php

namespace modules\entity\pets;

class GhastPet extends Pet {

	const NETWORK_ID = 41;

	public $width = 6;
	public $length = 6;
	public $height = 6;

	public function getName() {
		return "Ghast Pet";
	}

}
