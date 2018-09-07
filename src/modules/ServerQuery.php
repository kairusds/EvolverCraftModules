<?php

namespace modules;

/**
 * Class for querying Minecraft: PE Servers
 */
class ServerQuery {
	
	const STATISTICS = 0x00;
	const HANDSHAKE = 0x09;
	
	private $socket;
	private $players;
	private $info;
	
	public static function connect($ipAddress, int $port = 19132, int $timeout = 3) {
		return new ServerQuery($ipAddress, $port, $timeout);
	}
	
	public function __construct($ipAddress, int $port = 19132, int $timeout = 3) {
		$this->socket = @fsockopen("udp://" . $ipAddress, $port, $errno, $errstr, $timeout);
		if($errno || !$this->socket) {
			throw new \Exception("Could not open socket, error: " . $errstr);
		}
		stream_set_timeout($this->socket, $timeout);
		stream_set_blocking($this->socket, true);
		try {
			$challenge = $this->getChallenge();
			$this->getStatus($challenge);
		}
		catch(\Exception $e) {
			fclose($this->socket);
			throw new \Exception($e->getMessage());
		}
		fclose($this->socket);
	}
	
	public function getInfo() {
		return isset($this->info) ? $this->info : null;
	}
	
	public function getPlayers() {
		return isset($this->players) ? $this->players : null;
	}
	
	private function getChallenge() {
		$data = $this->writeData(self::HANDSHAKE);
		if(!$data) {
			throw new \Exception("Failed to receive challenge.");
		}
		return pack("N", $data);
	}
	
	public function getStatus($challenge) {
		$data = $this->writeData(self::STATISTICS, $challenge . pack("c*", 0x00, 0x00, 0x00, 0x00));
		if(!$data) {
			throw new \Exception("Failed to retrieve server status.");
		}
		$last = "";
		$info = [];
		$data = substr($data, 11);
		$data = explode("\x00\x00\x01player_\x00\x00", $data);
		if(count($data) !== 2) {
			throw new \Exception("Failed to get server's response.");
		}
		$players = substr($data[1], 0, -2);
		$data = explode("\x00", $data[0]);
		$keys = [
			"hostname" => "hostname",
			"gametype" => "gametype",
			"version" => "version",
			"plugins" => "plugins",
			"map" => "map",
			"numplayers" => "players",
			"maxplayers" => "maxplayers",
			"hostport" => "hostport",
			"hostip" => "hostip",
			"game_id" => "gamename"
		];
		foreach($data as $key => $Value) {
			if(~$Key & 1) {
				if(!array_key_exists($value, $keys)) {
					$last = null;
					continue;
				}
				$last = $keys[$value];
				$info[$last] = "";
			}elseif($last != null) {
				$info[$last] = mb_convert_encoding($value, "UTF-8");
			}
		}
		$info["players"] = intval($info["players"]);
		$info["maxplayers"] = intval($info["maxplayers"]);
		$info["hostport"] = intval($info["hostport"]);
		if($info["plugins"]) {
			$data = explode(": ", $info["plugins"], 2);
			$info["rawplugins"] = $info["plugins"];
			$info["software"] = $data[0];
			if(count($data) == 2) {
				$info["plugins"] = explode("; ", $data[ 1 ]);
			}
		}else {
			$info["software"] = "vanilla";
		}
		if(empty($players)) {
			$this->players = null;
		}else {
			$this->players = explode("\x00", $players);
		}
	}
	
	private function writeData($command, $append = "") {
		$command = pack("c*", 0xFE, 0xFD, $command, 0x01, 0x02, 0x03, 0x04) . $append;
		$length  = strlen($command);
		if($length !== fwrite($this->socket, $command, $length)) {
			throw new \Exception("Failed to write on socket.");
		}
		$data = fread($this->socket, 4096);
		if(!$data) {
			throw new \Exception("Failed to read from socket.");
		}
		if(strlen($data) < 5 || $data[0] != $command[2]) {
			return false;
		}
		return substr($data, 5);
	}
	
}