<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Framework\App as FrameworkApp;
use Strukt\Console\Color;
use Strukt\Ref;

/**
* package:info  Information on packages
* 
* Usage:
*	
*      package:info <name>
*
* Arguments:
*
*      name     Package name
*/
class PackageInfo extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$packages = FrameworkApp::getRepo();
		$installed = FrameworkApp::packages("installed");

		$name = $in->get("name");
		if(!in_array($name, $installed))
			new \Strukt\Raise(sprintf("Package [%s] is not installed!", $name));

		$cls = $packages[$name];
		if(class_exists($cls))
			$pkg = Ref::create($cls)->make()->getInstance();

		$name = $pkg->getName();
		$is_pub = $pkg->isPublished();
		$req = $pkg->getRequirements();
		$files = $pkg->getFiles();
		$modules = $pkg->getModules();

		$out->add(sprintf("Name: %s\n", $name));
		$out->add(sprintf("Published: %s\n", ["False", "True"][$is_pub]));

		if(!empty($req))
			$out->add(sprintf("Requirements: %s\n", $req));

		if(!empty($files))
			$out->add(sprintf("Files: %s\n", Color::write("yellow", implode("\n", $files))));

		if(!empty($modules))
			$out->add(sprintf("Modules: %s\n", $modules));

		$out->add("Settings:");
		foreach(["App:Idx", "App:Cli"] as $type){

			$out->add(sprintf("\n Type: %s", Color::write("blue", $type)));
			$settings = $pkg->getSettings($type);
			foreach(["commands", "middleware", "providers"] as $facet){

				$classes = $settings[$facet];
				if(!empty($classes)){

					$out->add(Color::write("green", sprintf("\n  Facet: %s\n", ucfirst($facet))));
					$out->add(Color::write("yellow", "   ".implode("\n   ", $classes)));
				}
			}
		}
	}
}