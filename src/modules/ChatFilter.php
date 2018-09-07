<?php

namespace modules;

use modules\chat\ChatClasser;
use pocketmine\utils\TextFormat;

/**
 * Class to check for allowed message
 * (prevent passwords in chat, prevent dating, short and repeating messages)
 */
class ChatFilter {
	/**@var ChatClasser*/
	protected $profanityChecker;
	/**@var array*/
	protected $recentMessages = array();
	/**@var bool*/
	protected $enableMessageFrequency;
	/**@var array*/
	protected $recentChat = array();

	public function __construct($enableMsgFrequency = true) {
		$this->profanityChecker = new ChatClasser();
		$this->enableMessageFrequency = $enableMsgFrequency;
	}

	/**
	 * Clears the recent chat filter (for spam protection)
	 *
	 * @return null
	 */
	public function clearRecentChat() {
 		$this->recentChat = array();
 	}

	/**
	 * Check for valid message
	 *
	 * @param LbPlayer $player
	 * @param string $message
	 * @param boolean $needCheck
	 * @return boolean
	 */
	public function check($player, $message, $needCheck = true) {
		// Check the message and log the result.
		$checkResult = $this->profanityChecker->check($message);

		$errorMessage = $this->getErrorMessage($message, $player);
		if (!empty($errorMessage)) {
			$player->sendMessage($errorMessage);
			return false;
		}
		if($needCheck){
			if ($this->enableMessageFrequency) {
				$this->recentChat[$player->getID()] = true;
			}
			$this->recentMessages[$player->getID()] = $message;
		}
		return true;
	}

	/**
	 * Get message with suitable error
	 *
	 * @param string $message
	 * @param Player $player
	 * @return string
	 */
	private function getErrorMessage($message, $player) {
		$errorMsg = '';

		if (strlen($message) === 0) {
			$errorMsg = TextFormat::RED . '§c- That daym message is too short.';
		} elseif (isset($this->recentChat[$player->getID()])) {
 			/* player already posted message in last 3 seconds */
 			$errorMsg = TextFormat::RED . '§c- You are chatting too fast.';
 		} elseif (isset($this->recentMessages[$player->getID()]) &&
			$this->recentMessages[$player->getID()] === $message) {
			/* player's message repeated his previous message */
			$errorMsg = TextFormat::RED . '§c- You cant repeat what you\'ve said before.';
		} elseif ($this->profanityChecker->getIsProfane()) {
			$errorMsg = TextFormat::RED . '§c- Hey, you can\'t say that.';
		} elseif ($this->profanityChecker->getIsDating()) {
			$errorMsg = TextFormat::RED . '§c- This is not a dating server.';
		} elseif ($this->profanityChecker->getIsAdvertising()) {
			$errorMsg = TextFormat::RED . '§c- Heh, advertising eh?';
		}

		return $errorMsg;
	}

}
