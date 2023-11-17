<?php

if(!function_exists("phar")){

	env("phar", false);
	function phar(bool $enable = null){

		if($enable)
			env("phar", $enable);

		return env("phar");
	}
}

if(!function_exists("config")){

	function config(string $key, array|string $options = null){

		if(!reg()->exists("config")){

			$cfg_path = str("cfg/");
			if(env("phar"))
				if(Phar::running())
					$cfg_path = $cfg_path->prepend("phar:///");

			$ini_files = glob($cfg_path->concat("*.ini")->yield());
			foreach($ini_files as $ini_file){

				$facet = str($ini_file)->replace([$cfg_path->yield(),".ini"],"")->yield();
				$configs = parse_ini_file($ini_file);
				foreach($configs as $name=>$val)
					reg(sprintf("config.%s.%s", $facet, $name), $val);
			}

			if(reg("config")->exists("app")){
				
				$app_config = reg("config.app");
				$app_name = $app_config->get("app-name");
				$app_config->remove("app-name");
				$app_config->set("name", $app_name);
			}
		}

		$nkey = sprintf("config.%s", rtrim($key, "*"));
		if(str($key)->endsWith("*"))
			return arr(array_flip(reg($nkey)->getKeys()))->each(function($k, $v) use($nkey){

				return reg($nkey)->get($k);

			})->yield();

		if(!is_null($options))
			reg(sprintf("config.%s", $key), $options);

		if(reg("config")->exists($key))
			return reg("config")->get($key);

		return null;
	}
}