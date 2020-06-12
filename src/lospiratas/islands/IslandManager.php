<?php

namespace lospiratas\islands;

use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\block\Block;

class IslandManager{

	public $islandSize;

	public $spawnBounds;
	public $spawnCurl=0;

	private $currentIsland;

	private $world;
	private $plugin;

	public function __construct(Islands $plugin, Level $level, int $islandSize, int $spawnCurl=0){
		$this->plugin = $plugin;
		$this->world = $level;
		$this->islandSize = $islandSize-1;
		$this->spawnCurl = $spawnCurl;

		$this->initSpawn();
	}


	public function getIslandsSize(){
		return $this->islandSize;
	}


	public function initSpawn(){
		$spawnIslands = $this->getIslandsFromCurl($this->spawnCurl);

		$this->currentIsland = end($spawnIslands);
	}

	public function isIslandSpawn($island){
		return $this->getCurlFromIsland($island) <= $this->spawnCurl;
	}

	public function isInSpawn(Player $player){
		
		$island = $this->getIslandFromPos($player);

		return $this->isIslandSpawn($island);
	}

	public function initIsland(Player $player){
	    $this->currentIsland = $this->getNextIsland($this->currentIsland);
	  
	    $this->constructIsland($this->currentIsland);
	}

	public function isInIsland(Player $player){
		
		$island = $this->getIslandFromPos($player);

		return $island;
	}

	public function decodeIslandLocation($island){
		$xy = explode(":", $island);
		$x = $xy[0];
		$y = $xy[1];

		return array($x, $y);
	}

	public function encodeIslandLocation($x, $y){
		return "{$x}:{$y}";
	}

	public function getIslandsFromCurl($curl=1){
	    $x = $curl;
	    $y = $curl;
	    
	    $curlsize = $curl*8;
	    
	    $sidesize = $curlsize/4;
	    $side=0;
	    
	    $islands = [];
	    
	    for($side=0; $side<4; $side++){
	        // 0 = right (x)(-y)
	        // 1 = down (-x)(y*-1)
	        // 2 = left (+y)(x*-1)
	        // 3 = top (+x)(y)
	        switch($side){
	            case 0: // right
	            
	                for($i=0; $i<$sidesize; $i++){
	                    $y--;
	                    $islands[] = $this->encodeIslandLocation($x, $y);
	                }
	                
	            break;
	            
	            case 1: // down
	            
	                for($i=0; $i<$sidesize; $i++){
	                    $x--;
	                    $islands[] = $this->encodeIslandLocation($x, $y);
	                }
	                
	            break;
	            
	            case 2: // left
	            
	                for($i=0; $i<$sidesize; $i++){
	                    $y++;
	                    $islands[] = $this->encodeIslandLocation($x, $y);
	                }
	            
	            break;
	            
	            case 3: // top
	            
	                for($i=0; $i<$sidesize; $i++){
	                    $x++;
	                    $islands[] = $this->encodeIslandLocation($x, $y);
	                }
	            break;
	        }
	    }
	    
	    return $islands;
	}


	public function getCurlFromIsland($island){
		$pos = $this->decodeIslandLocation($island);
		$x = abs($pos[0]);
		$y = abs($pos[1]);

		$curl = ($x > $y) ? $x : $y;
		return $curl;
	}

	public function getGridPositionFromIndex($xy){
		return abs($xy);
	}

	public function getNextIsland($island){
		$pos = $this->decodeIslandLocation($island);
		$x = $pos[0];
		$y = $pos[1];

		if(($x>=0) && ($y>=0) && $x == $y){ // end of curl

			$x = $x+1;
			return $this->encodeIslandLocation($x, $y);

		}else{ // island in same curl

			$curl = $this->getCurlFromIsland($island);
			$islands = $this->getIslandsFromCurl($curl);

			$key = array_search($island, $islands);

			return $islands[$key+1];

		}
	}

	public function getIslandBoundings($island){
	    $pos = $this->decodeIslandLocation($island);


		$x = ($pos[0] * $this->getIslandsSize());
		$z = ($pos[1] * $this->getIslandsSize());

		$curl = $this->getCurlFromIsland($island);
		$gridX = $this->getGridPositionFromIndex($pos[0]);
		$gridY = $this->getGridPositionFromIndex($pos[1]);

		// bounding increase 1 block every new curl prevent island overlapping
		if($x > 0) $x = $x + $gridX; 
		if($z > 0) $z = $z + $gridY;
		if($x < 0) $x = $x - $gridX;
		if($z < 0) $z = $z - $gridY;

		if($x>=0){
			$x2 = $x+$this->getIslandsSize();
		}else{
			$x2 = $x+$this->getIslandsSize();
		}

		if($z>=0){
			$z2 = $z+$this->getIslandsSize();
		}else{
			$z2 = $z+$this->getIslandsSize();
		}

		$y = 16; // min height
	    $y2 = 16; // max height

	    $pos1 = new Position($x, $y, $z);
	    $pos2 = new Position($x2, $y2, $z2);

	    return array($pos1, $pos2);
	}


	public function getIslandFromPos(Player $player){
		$posX = $player->getX();
		$posZ = $player->getZ();

		$size = $this->getIslandsSize()+1;

		$x = (floor($posX / $size) * $size)/$size;
		$z = (floor($posZ / $size) * $size)/$size;

		return $this->encodeIslandLocation($x, $z);
	}


	public function constructIsland($island){
		$boundings = $this->getIslandBoundings($island);

		$center = ceil($this->getIslandsSize()/2);

		$centerX = $boundings[0]->getX() + $center;
		$centerZ = $boundings[0]->getZ() + $center;

		$position = new Position($centerX, 9, $centerZ);
				

		$filename = "island.json";
		
		if(!file_exists($this->plugin->getDataFolder()."schemes/".$filename)) return false;

		$data = file_get_contents($this->plugin->getDataFolder()."schemes/".$filename);
		$layers = json_decode($data);

		$boxWidth = count($layers[0][0]);
		$boxDepth = count($layers[0]);
		$boxHeight = count($layers);


		$initialX = $centerX-(intval($boxWidth/2));
		$initialZ = $centerZ-(intval($boxDepth/2));
		$initialY = 9;

		for($positionY=0; $positionY < count($layers); $positionY++){
		    
		    for($positionZ=0; $positionZ < count($layers[$positionY]); $positionZ++){
		        
		        for($positionX=0; $positionX < count($layers[$positionY][$positionZ]); $positionX++){
		            
		            $posX = $initialX+$positionX;
		            $posY = $initialY+$positionY;
		            $posZ = $initialZ+$positionZ; 

		            $position = new Position($posX, $posY, $posZ);
		            $blockData = $layers[$positionY][$positionZ][$positionX];
		            $blockData = explode(":", $blockData);

		            $blockId = $blockData[0];

		            if($blockId != 0){
			            $blockMeta = $blockData[1];

			            $block = Block::get($blockId);
			            $block->setDamage($blockMeta);

			            $this->world->loadChunk($posX, $posZ);

			            $this->world->setBlock($position, $block, false, false);
		        	}
		            
		        }
		    }
		}

	}




}