<?php

namespace modules\task\crate;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;
use modules\entity\FloatingText;
use pocketmine\level\Position;
use modules\Crate;

class SecondStepTask extends Task {
	
    private $crate;
    private $block;
    private $item;
    private $player;

    public function __construct(Crate $crate, Block $block, Item $item, Player $player) {
        $this->crate = $crate;
        $this->block = $block;
        $this->item = $item;
        $this->player = $player;
    }
    
    public function getName() {
    	$name = "";
        $item = $this->item;
        if($item->hasEnchantments()) {
        	$name = "§b" . $item->getName();
        }
        if($item->hasCustomName()) {
        	$name = "§b" . $item->getCustomName();
        }
        if(!$item->hasEnchantments()) {
        	$name = $item->getName();
        }
        if($item->getCount() > 0) {
        	$name .= " x" . $item->getCount();
        }
        return $name;
    }
    
    public function onRun($currentTick) {
    	$nametag = new FloatingText(new Position($this->block->x + 0.5, $this->block->y + 1.3, $this->block->z + 0.5), $this->getName());
        $nametag->spawnTo($this->player);
        if($this->player->getInventory()->canAddItem($this->item)) {
            $this->player->getInventory()->addItem($this->item);
        }
        $this->crate->secondStepResult($nametag);
    }
}
