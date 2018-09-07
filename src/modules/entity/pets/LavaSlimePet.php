<?php

namespace modules\entity\pets;

/**
 * LavaSlime AKA MagmaCube
 */
class LavaSlimePet extends Pet {
	
	const NETWORK_ID = 42;
	
	public function getName() {
		return "Lava Slime Pet";
	}
	
}