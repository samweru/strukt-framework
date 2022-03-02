<?php

namespace Strukt\Console\Command;

use Strukt\Console\Input;
use Strukt\Console\Output;
use Strukt\Core\Registry;
use LucidFrame\Console\ConsoleTable;

/**
* route:ls     Route List
*/
class RouteList extends \Strukt\Console\Command{

	public function execute(Input $in, Output $out){

		$routeCollection = Registry::getSingleton()->get("strukt.router");
		$routes = $routeCollection->getRoutes();

		$table = new ConsoleTable();
		$table->setHeaders(array('Method', 'Route', "Permission"));

		foreach($routes as $route){

			$table->addRow(array(

				$route["method"],
				$route["pattern"], 
				$route["permission"], 
			));
		}
		
		$table->setIndent(1)->display();
	}
}