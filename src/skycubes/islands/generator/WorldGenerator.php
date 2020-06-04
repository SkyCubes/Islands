<?php

namespace skycubes\islands\generator;

use pocketmine\Server;
use pocketmine\level\generator\Flat;


class WorldGenerator {
	private $plugin;

	public function __construct($plugin) {
		$this->plugin = $plugin;
	}

	public function createWorld(string $name) {

		$this->plugin->getServer()->generateLevel($name, null, 'pocketmine\level\generator\Flat', ["preset" => "3;minecraft:bedrock,5*minecraft:dirt,3*minecraft:sand,8*minecraft:water;"]);

	}
}
