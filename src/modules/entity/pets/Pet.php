<?php

namespace modules\entity\pets;

use pocketmine\entity\Creature;
use pocketmine\event\Timings;
use pocketmine\level\Level;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\math\Math;
use pocketmine\block\Air;
use pocketmine\block\Liquid;
use pocketmine\utils\TextFormat;

abstract class Pet extends Creature {

	private $owner = null;
	private $distanceToOwner = 0;

	public function saveNBT() {
	}
	
	public function getOwner() {
		return $this->owner;
	}
	
	public function setOwner(Player $player) {
		$this->owner = $player;
	}

	public function spawnTo(Player $player) {
		if(!isset($this->hasSpawned[$player->getLoaderId()]) and isset($player->usedChunks[Level::chunkHash($this->chunk->getX(), $this->chunk->getZ())])) {
			$this->hasSpawned[$player->getLoaderId()] = $player;
			$pk = new AddEntityPacket();
			$pk->eid = $this->getId();
			$pk->type = static::NETWORK_ID;
			$pk->x = $this->x;
			$pk->y = $this->y;
			$pk->z = $this->z;
			$pk->speedX = $this->motionX;
			$pk->speedY = $this->motionY;
			$pk->speedZ = $this->motionZ;
			$pk->yaw = $this->yaw;
			$pk->pitch = $this->pitch;
			$pk->metadata = $this->dataProperties;
			$player->dataPacket($pk);
			parent::spawnTo($player);
		}
	}

	public function updateMovement() {
		if($this->lastX !== $this->x || $this->lastY !== $this->y || $this->lastZ !== $this->z || $this->lastYaw !== $this->yaw || $this->lastPitch !== $this->pitch) {
			$this->lastX = $this->x;
			$this->lastY = $this->y;
			$this->lastZ = $this->z;
			$this->lastYaw = $this->yaw;
			$this->lastPitch = $this->pitch;
		}
		$this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, $this->yaw, $this->pitch);
	}

	public function attack($damage, EntityDamageEvent $source) {}
	
	public function sendPotionEffects(Player $player) {}
	
	public function move($dx, $dy, $dz) {
		$this->boundingBox->offset($dx, 0, 0);
		$this->boundingBox->offset(0, 0, $dz);
		$this->boundingBox->offset(0, $dy, 0);
		$this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);		
		return true;
	}

	public function getSpeed() {
		return 0.8;
	}

	public function updateMove() {
		$x = $this->owner->x - $this->x;
		$z = $this->owner->z - $this->z;
		if($x ** 2 + $z ** 2 < 4) {
			$this->motionX = 0;
			$this->motionZ = 0;
			$this->motionY = 0;
			return;
		} else {
			$diff = abs($x) + abs($z);
			$this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
			$this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
		}
		$this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
		$y = $this->owner->y - $this->y;
		$this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
		$dx = $this->motionX;
		$dz = $this->motionZ;
		$newX = Math::floorFloat($this->x + $dx);
		$newZ = Math::floorFloat($this->z + $dz);
		$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y), $newZ));
		if($block->getId() != 0 && !$block instanceof Liquid) {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y + 1), $newZ));
			if($block != 0 && !$block instanceof Liquid) {
				$this->motionY = 0;
				$this->returnToOwner();
				return;
			} else {
				if(!$block->canBeFlowedInto) {
					$this->motionY = 1.1;
				} else {
					$this->motionY = 0;
				}
			}
		} else {
			$block = $this->level->getBlock(new Vector3($newX, Math::floorFloat($this->y - 1), $newZ));
			if($block->getId() != 0 && !($block instanceof Liquid)) {
				$blockY = Math::floorFloat($this->y);
				if($this->y - $this->gravity * 4 > $blockY) {
					$this->motionY = -$this->gravity * 4;
				} else {
					$this->motionY = ($this->y - $blockY) > 0 ? ($this->y - $blockY) : 0;
				}
			} else {
				$this->motionY -= $this->gravity * 4;
			}
		}
		$dy = $this->motionY;
		$this->move($dx, $dy, $dz);
		$this->updateMovement();
	}

	public function onUpdate($currentTick) {
		if(!$this->owner instanceof Player || $this->owner->closed) {
			$this->close();
			return false;
		}
		if($this->closed) {
			return false;
		}
		$hasUpdate = parent::onUpdate($currentTick);
		if($this->isAlive()){
			$hasUpdate = true;
		}
		if($this->distance($this->owner) > 40) {
			$this->returnToOwner();
		}
		$this->updateMove();
		$this->checkChunks();
		return $hasUpdate;
	}

	public function returnToOwner() {
		$len = rand(2, 6);
		$x = (-sin(deg2rad($this->owner->yaw))) * $len  +  $this->owner->getX();
		$z = cos(deg2rad($this->owner->yaw)) * $len  +  $this->owner->getZ();
		$this->x = $x;
		$this->y = $this->owner->getY() + 1;
		$this->z = $z;
	}

}