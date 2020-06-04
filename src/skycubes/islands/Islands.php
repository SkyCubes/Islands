<?php
namespace skycubes\islands;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\block\Block;

use skycubes\islands\generator\WorldGenerator;


class Islands extends PluginBase implements Listener{

	private $config;
	private $translator;
	private $definitions;
	private $skyforms;
	private $economy;
	private $world;

	private $pos1 = [];
	private $pos2 = [];

	private $scheme = [];


	public function onLoad(){
		
		$this->definitions = new Definitions($this);
		
		@mkdir($this->getDataFolder());
		@mkdir($this->getDataFolder().$this->definitions->getDef('LANG_PATH'));
        foreach(array_keys($this->getResources()) as $resource){
			$this->saveResource($resource, false);
		}

	}

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);

		$this->translator = new Translate($this);

		$islandsWorldName = $this->config->get("IslandsWorld");
		if($islandsWorldName == "" || $islandsWorldName == null){
			$this->config->set("IslandsWorld", "default");
			$this->config->save();
		}

		if(!$this->getServer()->isLevelLoaded($islandsWorldName)){

			if($this->getServer()->loadLevel($islandsWorldName)){

				$this->world = $this->getServer()->getLevelByName($islandsWorldName);
				$this->getLogger()->info("§a".$this->translator->get('WORLD_LOADED', [$islandsWorldName]));

			}else{

				$generator = new WorldGenerator($this);
				$generator->createWorld($islandsWorldName);

				$this->world = $this->getServer()->getLevelByName($islandsWorldName);
				$this->getLogger()->info("§a".$this->translator->get('WORLD_CREATED', [$islandsWorldName]));

			}
			
		}


		// $this->skyforms = $this->getServer()->getPluginManager()->getPlugin("SkyForms");
		// $this->economy = $this->getServer()->getPluginManager()->getPlugin("Economy")->getEconomy();

		$this->getLogger()->info("§a".$this->translator->get('PLUGIN_SUCCESSFULLY_ENABLED', [$this->getFullName()]));
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "is":
				if(isset($args[0])) switch($args[0]){

					case 'tpworld':

						$pos = new Position(0, 50, 0, $this->world);
						$sender->teleport($pos);
					break;

					case 'set':
						if(isset($args[1])) switch($args[1]){
							case 'pos1':
								$x = $sender->getFloorX();
								$y = $sender->getFloorY();
								$z = $sender->getFloorZ();

								$this->pos1[$sender->getName()] = array(
									"x" => $x,
									"y" => $y,
									"z" => $z
								);

								$sender->sendMessage("p1 ok");
							break;

							case 'pos2':
								$x = $sender->getFloorX();
								$y = $sender->getFloorY();
								$z = $sender->getFloorZ();

								$this->pos2[$sender->getName()] = array(
									"x" => $x,
									"y" => $y,
									"z" => $z
								);

								$sender->sendMessage("p2 ok");
							break;

							default:
								$sender->sendMessage("wrong usage");
							break;
						}else{
							$sender->sendMessage("wrong usage");
						}
					break;

					case 'createscheme':

						if(isset($args[1])){

							if(isset($this->pos1[$sender->getName()]) && isset($this->pos2[$sender->getName()])){
								$pPos1 = $this->pos1[$sender->getName()];
								$pPos2 = $this->pos2[$sender->getName()];

								$pos1 = new Position($pPos1["x"], $pPos1["y"], $pPos1["z"]);
								$pos2 = new Position($pPos2["x"], $pPos2["y"], $pPos2["z"]);

								$level = $sender->getLevel();

								if($this->createScheme($level, $pos1, $pos2)){
									$sender->sendMessage("scheme saved");
								}
							}else{
								$sender->sendMessage("missing pos1 and/or pos2");
							}

						}else{
							$sender->sendMessage("missing scheme name");
						}
					break;
					
					case 'createisland':
						$this->createIsland($sender);
					break;

					default:
						return true;
					break;
				}
			break;

			default:
			break;
		}
		return true;
	}


	public function createScheme(Level $level, Position $pos1, Position $pos2){

		// if($pos1->getY() > $pos2->getY()){
		// 	$tempPos1 = $pos1;
		// 	$tempPos2 = $pos2;
		// 	$pos1 = $tempPos2;
		// 	$pos2 = $tempPos1;
		// }


		if($pos1->getX() > $pos2->getX()){
			$x1 = $pos2->getX();
			$x2 = $pos1->getX();
		}else{
			$x1 = $pos1->getX();
			$x2 = $pos2->getX();
		}
		$xN = abs($x1-$x2);

		if($pos1->getY() > $pos2->getY()){
			$y1 = $pos2->getY();
			$y2 = $pos1->getY();
		}else{
			$y1 = $pos1->getY();
			$y2 = $pos2->getY();
		}
		$yN = abs($y1-$y2);

		if($pos1->getZ() > $pos2->getZ()){
			$z1 = $pos2->getZ();
			$z2 = $pos1->getZ();
		}else{
			$z1 = $pos1->getZ();
			$z2 = $pos2->getZ();
		}
		$zN = abs($z1-$z2);


		$layers = [];
		$layer = 0;

		for($y=0; $y<=$yN; $y++){

			$blockZ = 0;

			for($z=0; $z<=$zN; $z++){

				$blockX = 0;

				for($x=0; $x<=$xN; $x++){

					$posX = ($x1<$x2) ? $x1+$x : $x1-$x;
		    		$posY = ($y1<$y2) ? $y1+$y : $y1-$y;
		    		$posZ = ($z1<$z2) ? $z1+$z : $z1-$z;
					
					$position = new Position($posX, $posY, $posZ);
					$block = $level->getBlock($position);

					$layers[$layer][$blockZ][$blockX] = $block->getId().":".$block->getDamage();

					$blockX++;

				}

				$blockZ++;

			}

			$layer++;

		}

		$this->scheme = $layers;
		return true;
	}

	public function createIsland(Player $player){

		$layers = $this->scheme;

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
		            $blockData = $layers[$positionY][$positionZ][$positionX];
		            $blockData = explode(":", $blockData);

		            $blockId = $blockData[0];
		            $blockMeta = $blockData[1];

		            $block = Block::get($blockId);
		            $block->setDamage($blockMeta);

		            $player->getLevel()->setBlock($position, $block, false, false);
		            $player->teleport($playerNewPosition);
		            
		        }
		    }
		}
	}

	/** 
    * Returns selected language in config.yml
    * @access public
    * @return String
    */
	public function getLanguage(){
		return $this->config->get('Language');
	}

}