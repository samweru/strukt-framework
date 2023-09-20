<?php

namespace Strukt\Framework;

use Strukt\Framework\Contract\Module;
use Strukt\Fs;

class Application{

	public function __construct(){

		//
	}

	public function register(Module $module){

		$ns = $module->getNamespace();
		$alias = $module->getAlias();
		$dir = $module->getBaseDir();

		list($app_name, 
				$module_name, 
				$module_class) = explode("\\", $ns);

		$base_ns = str($app_name)->concat("\\")->concat($module_name)->yield();
		$facets = array_flip(config("module.folder*"));

		arr(Fs::lsr($dir))->each(function($k, $v) use($dir, $base_ns, $facets, $alias, $module_class){

			$base_path = trim(str($v)->replace([$dir,".php"], "")->yield(),"/");
			if(!str($base_path)->equals($module_class)){

				list($facet, $class) = str($base_path)->split("/");

				$base_alias = null;
				if(array_key_exists($facet, $facets))
					$base_alias = sprintf("%s.%s.%s", strtolower($alias), $facets[$facet], $class);

				$facet_ns = str($base_path)
								->replace("/","\\")
								->prepend(str($base_ns)
											->concat("\\")
											->yield())->yield();

				reg(sprintf("nr.%s", $base_alias), $facet_ns);
			}
		});
	}

	public function run(){

		//
	}
}