<?php

namespace net.tee7even.presents.task;

use pocketmine\scheduler\Task;
use modules\Crate;

class ThirdStepTask extends Task {
	
    private $crate;

    public function __construct(Crate $crate) {
        $this->crate = $crate;
    }

    public function onRun($currentTick) {
        $this->crate->close();
    }
}
