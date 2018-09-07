<?php

namespace Kits\items;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Snowball;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\item\Item;

class FlyingTNT extends Snowball {
	
	public $shouldExplode = false;
	
	public function onUpdate($currentTick) {
		if($this->closed) {
            return false;
        }
        $this->timings->startTiming();
        $hasUpdate = parent::onUpdate($currentTick);
		if($this->shootingEntity instanceof Player) {
			$bb = $this->getBoundingBox();
			if(($this->hadCollision || $this->onGround || count($this->level->getCollisionBlocks($bb, true)) > 0) && $hasUpdate) {
				$x = round($this->x);
				$z = round($this->z);
				$iterNum = 0;
				while($this->level->getBlock(new Vector3($x, round($this->y), $z)->getId() !== 0) {
					$x = $x > $this->shootingEntity->x ? $x - 1 : $x + 1;
					$z = $z > $this->shootingEntity->z ? $z - 1 : $z + 1;
					if($iterNum < 5) {
						$iterNum++;
					} else {
						break;
					}
				}
				$y = round($this->y + $this->shootingEntity->height / 2);
				$position = new Vector3($x, $y, $z);
				$this->getLevel()->setBlock($position, Block::get(Block::TNT));
				if($this->shouldExplode){
					$block = $this->getLevel()->getBlock($position);
					$block->onActivate(Item::get(Item::FLINT_STEEL), $this->shootingEntity);
				}
				$this->kill();
				$hasUpdate = false;
			}
		}
		$this->timings->stopTiming();
        return $hasUpdate;
	}
	
	public function spawnTo(Player $player) {
		$pk = new AddItemEntityPacket;
		$pk->eid = $this->getID();
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->item = Item::get(Block::TNT);
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$player->dataPacket($pk);
		$this->item = $pk->item;
		Entity::spawnTo($player);
	}
}
