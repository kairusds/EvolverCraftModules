<?php

namespace modules\entity\pets;

class StrayPet extends Pet {
	
	const NETWORK_ID = 46;
	
	public function getName() {
		return "Stray Pet";
	}
	
}