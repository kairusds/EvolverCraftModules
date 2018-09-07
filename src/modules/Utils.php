<?php

namespace modules;

use pocketmine\utils\Utils as PmUtils;

class Utils extends PmUtils {
	
	const ALIGN_LEFT = 0;
	const ALIGN_CENTER = 1;
	const ALIGN_RIGHT = 2;

	public static function hasPermission($player, $permission) {
		$base = "";
		$nodes = explode(".", $permission);
		foreach($nodes as $key => $node) {
			$seperator = $key == 0 ? "" : ".";
			$base = "$base$seperator$node";
			if($player->hasPermission($base)) {
				return true;
			}
		}
		return false;
	}
	
	public static function alignString($text, $char = " ", $mode = self::ALIGN_CENTER) {
		$lengths = [];
		$lines = explode("\n", $text);
		foreach($lines as $i => $line) {
			$lengths[$i] = self::getLength($line);
		}
		$paddingLength = self::getLength($char);
		foreach($lines as $i => &$line) {
			$deficit = $lengths[$i];
			$need = round($deficit / $paddingLength);
			if($mode === self::ALIGN_LEFT) {
				$line .= str_repeat(" ", $need);
			}elseif($mode === self::ALIGN_RIGHT) {
				$line = str_repeat(" ", $need) . $line;
			}else{
				$need /= 2;
				$line = str_repeat(" ", (int) $need) . $line . str_repeat(" ", ceil($need));
			}
		}
		return is_array($lines) ? implode("\n", $lines) : $lines;
	}
	
	public static function str2hex($string) {
		$hex = "";
		for($i = 0; $i < strlen($string); $i++) {
			$ord = ord($string[$i]);
			$hexCode = dechex($ord);
			$hex .= substr("0" . $hexCode, -2);
		}
		return strtoupper($hex);
	}
	
	public static function hex2str($hex) {
		$string = "";
		for($i = 0; $i < strlen($hex) - 1; $i += 2) {
			$string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
		}
		return $string;
	}
	
	public static function translateColors($string) {
		return str_replace("&", "§", $string);
	}
	
}