<?php

namespace Strukt;

use Strukt\Env;
use Strukt\Core\Registry;
use Strukt\Console\DocBlockParser;

use Strukt\Console\Command\ApplicationGenerator;
use Strukt\Console\Command\ApplicationLoaderGenerator;
use Strukt\Console\Command\ApplicationExec;
use Strukt\Console\Command\RouterGenerator;
use Strukt\Console\Command\ModuleGenerator;
use Strukt\Console\Command\RouteList;
use Strukt\Console\Command\ShellExec;
use Strukt\Console\Command\CliUtil;
use Strukt\Console\Command\CliList;

/**
* Console Loader
*
* @author Moderator <pitsolu@gmail.com>
*/
class Console extends \Strukt\Console\Application{

	/**
	* Constructor loads Strukt Framework in-build applications
	*/
	public function __construct($load_native_cmds = true){

		$registry = Registry::getSingleton();

		parent::__construct(Env::get("cli_app_name"));

		$this->addCmdSect(Env::get("cli_label"));

		if($load_native_cmds){

			$this->add(new ApplicationGenerator);
			$this->add(new ApplicationLoaderGenerator);
			$this->add(new ApplicationExec);
			
			if($registry->exists("module-list")){

				$this->add(new RouterGenerator);
				$this->add(new ModuleGenerator);
				$this->add(new RouteList);
			}

			$this->add(new ShellExec);
			$this->add(new CliUtil);
			$this->add(new CliList);

			$config = new \Strukt\Framework\Configuration();
			$cmds = $config->get("commands");

			$cls = [];
			foreach($cmds as $cmd){

				$doc = new \Strukt\Console\DocBlockParser($cmd);
				$ls = $doc->parse();
				$alias = $ls["command"]["alias"];
				$cls[$alias] = $cmd;
			}

			$cmd_names = parse_ini_file(Env::get("rel_cmd_ini"));

			foreach($cmd_names as $key => $val){

				if(is_string($val))
					$this->addCmdSect(sprintf("\n%s", $val));

				if(is_array($val))
					foreach($val as $cmd)
						$this->add(new $cls[$cmd]);
			}
		}
	}
}