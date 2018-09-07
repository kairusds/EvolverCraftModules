<?php

namespace modules\entity\pets;

class WitchPet extends Pet {
	
	const NETWORK_ID = 45;
	
	public function getName() {
		return "Witch Pet";
	}
	
}