<?php

namespace skycubes\islands;

use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\block\Block;

class IslandManager{

	public $islandSize;

	public $islandsBounds = [];
	public $spawnBounds;
	public $spawnCurl;

	private $currentIsland;

	private $world;
	private $plugin;

	public function __construct(Islands $plugin, Level $level){
		$this->plugin = $plugin;
		$this->world = $level;
	}


	public function setSpawnCurl($curl){
		$this->spawnCurl = $curl;
	}

	public function setIslandsSize(int $size){
		$this->islandSize = $size-1;
	}
	public function getIslandsSize(){
		return $this->islandSize;
	}


	public function initSpawn(){

		$spawn = $this->getIslandBoundings($this->encodeIslandLocation(0, 0));
		$pos1 = $spawn[0];
		$pos2 = $spawn[1];

		if($pos1->getX() > $pos2->getX()){
			$x1 = $pos2->getX();
			$x2 = $pos1->getX();
		}else{
			$x1 = $pos1->getX();
			$x2 = $pos2->getX();
		}
		$xN = abs($x1-$x2);


		if($pos1->getZ() > $pos2->getZ()){
			$z1 = $pos2->getZ();
			$z2 = $pos1->getZ();
		}else{
			$z1 = $pos1->getZ();
			$z2 = $pos2->getZ();
		}
		$zN = abs($z1-$z2);

		for($z=0; $z<=$zN; $z++){
			for($x=0; $x<=$xN; $x++){

				$posX = ($x1<$x2) ? $x1+$x : $x1-$x;
	    		$posZ = ($z1<$z2) ? $z1+$z : $z1-$z;

				$position = new Position($posX, 16, $posZ);
				
				$this->world->setBlock($position, Block::get(7), false, false);

			}

		}

	    $this->currentIsland = $this->encodeIslandLocation(0, 0);

	    $this->spawnBounds = $this->getIslandBoundings($this->encodeIslandLocation(0, 0));
	}

	public function isInSpawn(Player $player){
		if($player->getLevel() != $this->world) return false;
		if(($player->getX() > $this->spawnBounds[0]->getX()) && ($player->getX() < $this->spawnBounds[1]->getX())){
			if(($player->getY() > $this->spawnBounds[0]->getY()) && ($player->getY() < $this->spawnBounds[1]->getY())){
				if(($player->getZ() > $this->spawnBounds[0]->getZ()) && ($player->getZ() < $this->spawnBounds[1]->getZ())){
					return true;
				}
			}
		}
		return false;
	}

	public function initIsland(Player $player){
		$island = $this->getNextIsland($this->currentIsland);

	    $this->currentIsland = $island;
	  
	    $bounds = $this->getIslandBoundings($island);
	    // var_dump($bounds);
	    $block = 236;
	    $meta = mt_rand(0, 15);

	    $pos1 = $bounds[0];
	    $pos2 = $bounds[1];

	    $this->world->setBlock($pos1, Block::get($block, $meta), false, false);
	    $this->world->setBlock($pos2, Block::get($block, $meta), false, false);

	    if($pos1->getX() > $pos2->getX()){
			$x1 = $pos2->getX();
			$x2 = $pos1->getX();
		}else{
			$x1 = $pos1->getX();
			$x2 = $pos2->getX();
		}
		$xN = abs($x1-$x2);

		if($pos1->getZ() > $pos2->getZ()){
			$z1 = $pos2->getZ();
			$z2 = $pos1->getZ();
		}else{
			$z1 = $pos1->getZ();
			$z2 = $pos2->getZ();
		}
		$zN = abs($z1-$z2);

		for($z=0; $z<=$zN; $z++){

			for($x=0; $x<=$xN; $x++){

				$posX = ($x1<$x2) ? $x1+$x : $x1-$x;
	    		$posZ = ($z1<$z2) ? $z1+$z : $z1-$z;
				
				$position = new Position($posX, 16, $posZ);
				
				$this->world->setBlock($position, Block::get($block, $meta), false, false);

			}

		}

	    $this->islandsBounds[$player->getName()] = array($bounds[0], $bounds[1]);
	    $this->constructIsland($island);
	    return $island;
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

	function getIslandBoundings($island){
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

		// return "x: $centerX, z: $centerZ";

		$position = new Position($centerX, 17, $centerZ);
				

		$filename = "teste.json";
		
		if(!file_exists($this->plugin->getDataFolder()."schemes/".$filename)) return false;

		$data = file_get_contents($this->plugin->getDataFolder()."schemes/".$filename);
		$layers = json_decode($data);

		$boxWidth = count($layers[0][0]);
		$boxDepth = count($layers[0]);
		$boxHeight = count($layers);


		$initialX = $centerX-(intval($boxWidth/2));
		$initialZ = $centerZ-(intval($boxDepth/2));
		$initialY = 17;

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
		            $blockMeta = $blockData[1];

		            $block = Block::get($blockId);
		            $block->setDamage($blockMeta);

		            $this->world->setBlock($position, $block, false, false);
		            
		        }
		    }
		}

	}




}