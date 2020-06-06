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
		$this->islandSize = $size;
	}
	public function getIslandsSize(){
		return $this->islandSize;
	}


	public function initSpawn(){
		// $curlsize = $this->spawnCurl*8;
	 //    $distanceFromSpawn = ($curlsize/4)*$this->islandSize;

	 //    $x1 = $distanceFromSpawn*(-1);
	 //    $y1 = 0;
	 //    $z1 = $distanceFromSpawn*(-1);

	 //    $x2 = abs($distanceFromSpawn);
	 //    $y2 = 256;
	 //    $z2 = abs($distanceFromSpawn);

	 //    $pos1 = new Position($x1, $y1, $z1);
	 //    $pos2 = new Position($x2, $y2, $z2);

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
	    $block = mt_rand(1, 5);

	    $pos1 = $bounds[0];
	    $pos2 = $bounds[1];

	    echo $island."\n";
	    echo " start in x:".$pos1->getX()." z:".$pos1->getZ()."\n";
	    echo " end in x:".$pos2->getX()." z:".$pos2->getZ()."\n";
	    $this->world->setBlock($pos1, Block::get($block), false, false);
	    $this->world->setBlock($pos2, Block::get($block), false, false);

	 //    if($pos1->getX() > $pos2->getX()){
		// 	$x1 = $pos2->getX();
		// 	$x2 = $pos1->getX();
		// }else{
		// 	$x1 = $pos1->getX();
		// 	$x2 = $pos2->getX();
		// }
		// $xN = abs($x1-$x2);

		// if($pos1->getY() > $pos2->getY()){
		// 	$y1 = $pos2->getY();
		// 	$y2 = $pos1->getY();
		// }else{
		// 	$y1 = $pos1->getY();
		// 	$y2 = $pos2->getY();
		// }
		// $yN = abs($y1-$y2);

		// if($pos1->getZ() > $pos2->getZ()){
		// 	$z1 = $pos2->getZ();
		// 	$z2 = $pos1->getZ();
		// }else{
		// 	$z1 = $pos1->getZ();
		// 	$z2 = $pos2->getZ();
		// }
		// $zN = abs($z1-$z2);

		// for($z=0; $z<=$zN; $z++){

		// 	for($x=0; $x<=$xN; $x++){

		// 		$posX = ($x1<$x2) ? $x1+$x : $x1-$x;
	 //    		$posZ = ($z1<$z2) ? $z1+$z : $z1-$z;
				
		// 		$position = new Position($posX, 16, $posZ);
				
		// 		$this->world->setBlock($position, Block::get($block), false, false);

		// 	}

		// }

	    $this->islandsBounds[$player->getName()] = array($bounds[0], $bounds[1]);
	}

	public function isInIsland(Player $player){
		if(count($this->islandsBounds) > 0){
			foreach($this->islandsBounds as $bound){
				// var_dump($bound);
			}
		}else{
			return false;
		}
		$bounds = $this->islandsBounds[$player->getName()];
		if($player->getLevel() != $this->world) return false;
		if(($player->getX() > $bounds[0]->getX()) && ($player->getX() < $bounds[1]->getX())){
			if(($player->getY() > $bounds[0]->getY()) && ($player->getY() < $bounds[1]->getY())){
				if(($player->getZ() > $bounds[0]->getZ()) && ($player->getZ() < $bounds[1]->getZ())){
					return true;
				}
			}
		}
		return false;
	}

	public function decodeIslandLocation($island){
		$xy = explode(":", $island);
		$x = abs($xy[0]);
		$y = abs($xy[1]);

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
		$x = $pos[0];
		$y = $pos[1];

		$curl = ($x > $y) ? $x : $y;
		return $curl;
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
		$y = 16; // min height
		$z = ($pos[1] * $this->getIslandsSize());

		// $x2 = ($x < 0) ? $x - $this->getIslandsSize() : $x + $this->getIslandsSize();
		// $z2 = ($z < 0) ? $z - $this->getIslandsSize() : $z + $this->getIslandsSize();

		$x2 = $x+$this->getIslandsSize();
    	$z2 = $z+$this->getIslandsSize();

	    $y2 = 16; // max height

	    $pos1 = new Position($x, $y, $z);
	    $pos2 = new Position($x2, $y2, $z2);

	    return array($pos1, $pos2);
	}




}