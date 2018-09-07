<?php

namespace modules\task\crate;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\scheduler\Task;
use modules\Crate;
use pocketmine\util\Random;

class FirstStepTask extends Task {
	
    private $crate;
    private $block;
    private $item;

    public function __construct(Crate $crate, Block $block, Item $item) {
        $this->crate = crate;
        $this->block = $block;
        $this->item = item;
    }
    
    public function onRun($currentTick) {
        $itemEntity = $this->shootItem($this->block->getLevel(), $this->block, new Vector3(0, 0.3, 0));
        $this->chest->firstStepResult(itemEntity);
    }

    private function shootItem(Level $level, Vector3 $source, Vector3 $motion) {
    	$itemTag = NBT::putItemHelper($item);
		$itemTag->setName("Item");
        $itemEntity = Entity::createEntity("Item", $this->getChunk($source->getX() >> 4, $source->getZ() >> 4, true), new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $source->getX()),
				new DoubleTag("", $source->getY()),
				new DoubleTag("", $source->getZ())
			]),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("", $motion->x),
				new DoubleTag("", $motion->y),
				new DoubleTag("", $motion->z)
			]),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", lcg_value() * 360),
				new FloatTag("", 0)
			]),
			"Health" => new ShortTag("Health", 5),
			"Item" => $itemTag,
			"PickupDelay" => new ShortTag("PickupDelay", $delay)
		]));
		$itemEntity->spawnToAll();
		return $itemEntity;
    }
}