<?php

namespace modules\command;

use modules\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;

abstract class Command extends Command implements PluginIdentifiableCommand {
	
	private $plugin;

	public function __construct(Main $plugin, $name, $description, $usageMessage, array $aliases) {
		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->plugin = $plugin;
	}

	/**
	 * @return Main
	 */
	public function getPlugin() {
		return $this->plugin;
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * 
	 * @return bool
	 */
	public function execute(CommandSender $sender, $commandLabel, array $args) {
		if($this->testPermission($sender)) {
			return $this->run($sender, $args);
		} else {
			$sender->sendMessage($this->getPermissionMessage());
			return true;
		}
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 * 
	 * @return bool
	 */
	public abstract function run(CommandSender $sender, array $args);

}