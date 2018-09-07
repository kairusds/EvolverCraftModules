<?php

namespace modules\entity;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\network\protocol\{
	AddPlayerPacket,
	RemoveEntityPacket,
	SetEntityDataPacket
};
use pocketmine\{
	Player,
	Server
};
use pocketmine\utils\UUID;

class FloatingText {

    protected $pos;
    protected $text = "";
    protected $eid;
    protected $hasSpawned = [];

    public function __construct(Position $pos, $text) {
        $this->pos = $pos;
        $this->text = $text;
        $this->eid = bcadd("1095216660480", mt_rand(0, 0x7fffffff));
        $this->spawnToAll();
    }

    public function spawnToAll() {
    	$players = Server::getInstance()->getOnlinePlayers();
        foreach($players as $p) {
            $this->spawnTo($p);
        }
    }

    public function despawnFromAll() {
        $players = Server::getInstance()->getOnlinePlayers();
        foreach($players as $p) {
            $this->despawnFrom($p);
        }
    }
    
    public function setText($text) {
        $players = Server::getInstance()->getOnlinePlayers();
        $this->text = $text;
        $pk = new SetEntityDataPacket();
        $pk->eid = $this->eid;
        $pk->metadata = [
            Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text]
        ];
        Server::getInstance()->broadcastPacket($players, $pk);
    }
    
    public function getText() {
    	return $this->text;
    }
    
    public function spawnTo(Player $player) {
        if($player !== $this and !isset($this->hasSpawned[$player->getId()])) {
            $this->hasSpawned[$player->getId()] = $player;
            $pk = new AddPlayerPacket();
            $pk->eid = $this->eid;
            $pk->uuid = UUID::fromRandom();
            $pk->x = $this->pos->x - 0.15;
            $pk->y = $this->pos->y - 1.62;
            $pk->z = $this->pos->z - 0.15;
            $pk->speedX = 0;
            $pk->speedY = 0;
            $pk->speedZ = 0;
            $pk->yaw = 0;
            $pk->pitch = 0;
            $pk->item = Item::get(0);
            $flags = 0;
			$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
			$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
			$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
			$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
			$pk->metadata = [
				Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
				Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->text],
				Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
            ];
            $player->dataPacket($pk);
        }
    }

    public function despawnFrom(Player $player) {
        if(isset($this->hasSpawned[$player->getId()])) {
            unset($this->hasSpawned[$player->getId()]);
            $pk = new RemoveEntityPacket();
            $pk->eid = $this->eid;
            $player->dataPacket($pk);
        }
    }

}