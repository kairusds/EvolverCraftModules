<?php

namespace modules\entity\pets;

class HuskPet extends Pet {
	
	const NETWORK_ID = 47;
	
	public function getName() {
		return "Husk Pet";
	}
	
}