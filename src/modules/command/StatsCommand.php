<?php

namespace modules\command;

use modules\{
	Main,
	StatsManager
};
use pocketmine\Player;
use pocketmine\command\CommandSender;

class StatsCommand extends ModuleCommand {

	public function __construct(Main $plugin) {
		parent::__construct($plugin, "stats", "shows the player's stats", "/stats <player>");
		$this->setPermission("ec.command.stats");
	}

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 *
	 * @return bool
	 */
	public function run(CommandSender $sender, array $args) {
		$sender->sendMessage(($sender instanceof Player ? StatsManager::getStats($sender) : "This command can only be ran in-game."));
	}

}