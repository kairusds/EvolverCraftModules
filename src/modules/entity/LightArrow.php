<?php

namespace modules\entity;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\entity\Arrow;
use pocketmine\entity\Projectile;
use pocketmine\level\Explosion;
use pocketmine\Server;

class LightArrow extends Arrow {
	
	public function onUpdate($currentTick) {
		if($this->closed) {
			return false;
		}
		$hasUpdate = parent::onUpdate($currentTick);
		if($this->age > 1200 or $this->isCollided) {
			$this->kill();
			$hasUpdate = true;
		}
		$this->light();
		if($this->getLevel()->getServer()->lightningFire) {
			foreach($this->level->getNearbyEntities($this->boundingBox->grow(4, 3, 4), $this) as $entity) {
				if($entity instanceof Player) {
					$damage = mt_rand(8, 20);
					$ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageByEntityEvent::CAUSE_LIGHTNING, $damage);
					if($entity->attack($ev->getFinalDamage(), $ev) === true) {
						$ev->useArmors();
					}
				}
				$entity->setOnFire(mt_rand(3, 8));
			}
			if($entity instanceof Creeper) {
				$entity->setPowered(true, $this);
			}
		}
		return $hasUpdate;
	}
	
	public function light() {
		$pk = new AddEntityPacket();
		$pk->type = 93;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->metadata = [];
		foreach($this->getLevel()->getPlayers() as $player) {
			$player->dataPacket($pk);
			$pk1 = new ExplodePacket();
			$pk1->x = $this->x;
			$pk1->y = $this->y;
			$pk1->z = $this->z;
			$pk1->radius = 10;
			$pk1->records = [];
			$player->dataPacket($pk1);
		}
	}
	
}