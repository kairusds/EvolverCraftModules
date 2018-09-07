<?php

namespace modules\items;

use pocketmine\Player;
use pocketmine\entity\Arrow;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddItemEntityPacket;

class TeleportProjectile extends Arrow {
	
	private $item;
	protected $damage = 0;

	public function onUpdate($currentTick) {
        if($this->closed) {
            return false;
        }
        $this->timings->startTiming();
        $hasUpdate = parent::onUpdate($currentTick);
		if($this->shootingEntity instanceof Player) {
			$bb = $this->getBoundingBox();
			if((count($this->level->getCollisionBlocks($bb, true)) > 0 || $this->hadCollision) && $hasUpdate) {
				$x = round($this->x);
				$z = round($this->z);
				$iterNum = 0;
				while($this->level->getBlock(new Vector3($x, round($this->y), $z))->getId() !== 0)) {
					$x = $x > $this->shootingEntity->x ? $x - 1 : $x + 1;
					$z = $z > $this->shootingEntity->z ? $z - 1 : $z + 1;
					if($iterNum < 5) {
						$iterNum++;
					}else {
						break;
					}
				}
				$y = round($this->y + $this->shootingEntity->height);
				$this->shootingEntity->teleport(new Vector3($x, $y, $z));
				$this->kill();
				$hasUpdate = false;
			}
		}
		$this->timings->stopTiming();
		return $hasUpdate;
	}
	
	public function spawnTo(Player $player) {
		$pk = new AddItemEntityPacket;
		$pk->eid = $this->getId();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->item = Item::get(Item::COMPASS);
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$player->dataPacket($pk);
		$this->item = $pk->item;
		Entity::spawnTo($player);
	}
}