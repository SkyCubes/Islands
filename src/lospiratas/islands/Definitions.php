<?php

namespace lospiratas\islands;
use lospiratas\islands\Islands;
use \Exception;

class Definitions{

	protected const LANG_PATH = 'lang';

	private $plugin;

	public function __construct(Islands $plugin){

		$this->plugin = $plugin;

	}

	public function getLangPath(string $language){

		return $this->plugin->getDataFolder().self::LANG_PATH.'/'.$language.'.json';

	}


	public function getDef($def){

		return constant('self::'.$def);

	}

}

?>