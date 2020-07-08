<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Env;
use Strukt\Util\Str;
use Strukt\Fs;
use Strukt\Templator;
use Strukt\Raise;

/**
* generate:app     Generate Application
*
* Usage:
*
*       generate:app <application_name>
*
* Arguments:
*
*       application_name   application name
*                           underscored names changed to camel case
*                            example:app -> App, app_name -> AppName
*/
class ApplicationGenerator extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$raw_app_name = $in->get("application_name");

		$app_name = Str::create($raw_app_name)->toCamel();

		$root_dir = Env::get("root_dir");
		$app_dir = Env::get("rel_appsrc_dir");
		$mod_ini = Env::get("rel_mod_ini");
		$app_ini = Env::get("rel_app_ini");
		$tpl_app_dir = Env::get("rel_tplapp_dir");
		$tpl_appsrc_dir = Env::get("rel_tplappsrc_dir");
		$tpl_authmod_dir = Env::get("rel_tplauthmod_dir");

		$auth_mod_path = sprintf("%s/%s%s/AuthModule", 
								$root_dir, 
								$app_dir,
								$app_name);

		$mod_ini_path = sprintf("%s/%s", $root_dir, $mod_ini);

		$mod_ini_exists = Fs::isFile($mod_ini_path);

		if($mod_ini_exists){

			$mod_ini_contents = parse_ini_file($mod_ini_path);

			if(in_array("folder", array_keys($mod_ini_contents)))
				foreach($mod_ini_contents["folder"] as $folder)
					Fs::mkdir(sprintf("%s/%s", $auth_mod_path, $folder));

			$authmod_dir = str_replace(array($tpl_appsrc_dir, "App"), 
												array($app_dir, $app_name), 
												$tpl_authmod_dir);

			Fs::mkdir($authmod_dir);

			$files = Fs::lsr($tpl_app_dir);

			foreach($files as $file){

				if(!Fs::isFile($file))
					continue;
				
				$tpl_file = Fs::cat($file);

				$output = Templator::create($tpl_file, array(

					"app"=>$app_name->yield()
				));

				$base = str_replace($tpl_authmod_dir, $authmod_dir, 
										preg_replace("/\w+\.sgf$/", "", $file));

				if(!Fs::isPath($base))
					Fs::mkdir($base);

				$outputfile = Str::create($file)
					->replace("tpl/sgf/","")
					->replace("\\App\\", sprintf("/%s/", $app_name->yield()))
					->replace(".sgf", ".php")
					->replace("/", "\\");
					

				if($outputfile->contains("_AuthModule.php")){

					$module_name = sprintf("%sAuthModule.php", $app_name->yield());
					$outputfile = $outputfile->replace("_AuthModule.php", $module_name);
				}

				$outputfile = $outputfile->yield();

				if(!Fs::touchWrite($outputfile, $output))
					new Raise(sprintf("%s did not generate!", $outputfile));
			}

			$app_ini_content = Fs::cat($app_ini);

			$app_ini_output = Templator::create($app_ini_content, array(

				"app"=>$app_name->yield()
			));

			Fs::overwrite($app_ini, $app_ini_output);
			
			$out->add("Application genarated successfully.\n");
		}
		
		if(!$mod_ini_exists)
			$out->add(sprintf("Failed to find [%s] file!\n", $app_ini));
	}
}