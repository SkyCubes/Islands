<?php
namespace skycubes\islands;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\level\Position;


class Islands extends PluginBase implements Listener{


	public function onLoad(){

		
		@mkdir($this->getDataFolder());
        foreach(array_keys($this->getResources()) as $resource){
			$this->saveResource($resource, false);
		}

	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);


		$this->getLogger()->info("§aIslands ok");
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "criar":
				$this->criar($sender);
			break;

			default:
			break;
		}
		return true;
	}


	public function criar($player){

		$layers = [
			[
			    [1, 1, 1, 1],
			    [1, 1, 1, 1],
			    [1, 1, 1, 1],
			    [1, 1, 1, 1]
			],

			[
			    [2, 3, 3, 2],
			    [3, 3, 3, 3],
			    [3, 3, 3, 3],
			    [2, 3, 3, 2]
			],

			[
			    [0, 2, 2, 0],
			    [2, 3, 3, 2],
			    [2, 3, 3, 2],
			    [0, 2, 2, 0]
			],

			[
			    [0, 0, 0, 0],
			    [0, 2, 2, 0],
			    [0, 2, 2, 0],
			    [0, 0, 0, 0]
			]
		];

		$boxWidth = count($layers[0][0]);
		$boxDepth = count($layers[0]);
		$boxHeight = count($layers);


		$initialX = $player->getFloorX();
		$initialY = $player->getFloorY();
		$initialZ = $player->getFloorZ();

		$playerMiddleXPosition = ($boxWidth / 2) + $initialX;
		$playerMiddleZPosition = ($boxDepth / 2) + $initialZ;
		$playerYPosition = $boxHeight + $initialY;

		$playerNewPosition = new Position($playerMiddleXPosition, $playerYPosition, $playerMiddleZPosition);

		for($positionY=0; $positionY < count($layers); $positionY++){
		    
		    for($positionZ=0; $positionZ < count($layers[$positionY]); $positionZ++){
		        
		        for($positionX=0; $positionX < count($layers[$positionY][$positionZ]); $positionX++){
		            
		            $posX = $initialX+$positionX;
		            $posY = $initialY+$positionY;
		            $posZ = $initialZ+$positionZ; 

		            $position = new Position($posX, $posY, $posZ);
		            $blockId = $layers[$positionY][$positionZ][$positionX];
		            $player->getLevel()->setBlock($position, Block::get($blockId), false, false);
		            $player->teleport($playerNewPosition);
		            
		        }
		    }
		}

	}

}