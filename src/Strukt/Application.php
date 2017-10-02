<?php

namespace Strukt;

use Strukt\Rest\ResponseType\HtmlFileResponse;
use Strukt\Rest\Response;
use Strukt\Rest\Request;
use Strukt\Rest\Router;
use Strukt\Rest\Dispatcher;
use Strukt\Core\Map;
use Strukt\Core\Collection;
use Strukt\Core\Registry;
use Strukt\Annotation\Parser\Basic as BasicAnnotationParser;


/**
* Strukt Application Module Loader and Runner
*
* @author Moderator <pitsolu@gmail.com>
*/
class Application{

	/**
	* Store resolved module items
	*
	* @var array
	*/
	private $modules;

	/**
	* Name Registry
	*
	* @var \Strukt\Core\Map
	*/
	private $nr;

	/**
	* Constructor
	*/
	public function __construct(){

		$this->modules = array();
		$this->nr = new Map(new Collection("NameRegistry"));
	}

	/**
	* Register Strukt Modules
	*
	* @param \App\Module $module
	*
	* @return void
	*/
	public function register(\App\Module $module){

		$ns = $module->getNamespace();
		$alias = $module->getAlias();
		$dir = $module->getBaseDir();

		list($appName, 
				$moduleName, 
				$fmoduleName) = explode("\\", $ns);

		$baseNs = trim(str_replace($fmoduleName, "", $ns),"\\");
		$this->modules[$fmoduleName]["base-ns"] = $baseNs;
		
		$_alias = strtolower($alias);
		$this->nr->set(sprintf("%s.app.name", $_alias), $appName);
		$this->nr->set(sprintf("%s.name", $_alias), $moduleName);
		$this->nr->set(sprintf("%s.fname", $_alias), $fmoduleName);
		$this->nr->set(sprintf("%s.ns", $_alias), $ns);
		$this->nr->set(sprintf("%s.base.ns", $_alias), $baseNs);
		$this->nr->set(sprintf("%s.dir", $_alias), $dir);

		$rootDir = Registry::getInstance()->get("_dir");

		if(!Fs::isPath($rootDir))
			throw new \Exception(sprintf("Root dir [%s] does not exist!", $rootDir));

		$modIniFile = sprintf("%s/cfg/module.ini", $rootDir);

		if(!Fs::isFile($modIniFile))
			throw new \Exception("Could not find [cfg/module.ini] file!");

		$modSettings = parse_ini_file($modIniFile);

		if(!in_array("folder", array_keys($modSettings)))
			throw new \Exception("Module Ini file [cfg/module.ini] must specify [alias=>folder] list!");

		foreach($modSettings["folder"] as $key=>$fldr){

			foreach(glob(sprintf("%s/%s/*", $dir, $fldr)) as $file){

				$fname = str_replace(".php", "", basename($file));

				$this->modules[$fmoduleName][$fldr][] = $fname;

				$this->nr->set(sprintf("%s.%s.%s", 
									strtolower($alias), 
									strtolower($key), 
									$fname),
								sprintf("%s\%s\%s",
									$baseNs,
									$fldr,
									$fname));
			}
		}	
	}

	/**
	* Getter for Name Registry 
	*
	* @return \Strukt\Core\Map
	*/
	public function getNameRegistry(){

		return $this->nr;
	}

	/**
	* Create router
	*
	* Uses:
	*
	* {@link \Strukt\Rest\Router}
	*	|
	*	--{@link \Strukt\Rest\Dispatcher}
	*		|
	* 		--{@link \Strukt\Rest\Request}
	*		|
	*		--{@link \Strukt\Rest\Response}
	* 
	* @return Strukt\Rest\Router
	*/
	public function getRouter(){

		/**
		* @todo either cache annotations or cache router loaded
		*		with annotations for speed and efficiency
		*/
		$request = new Request();
		$response = new Response();
		$dispatcher = new Dispatcher($request, $response);
		$router = new Router($dispatcher);

		$registry = Registry::getInstance();

		$cache = null;
		$routerParams = null;
		if($registry->exists("cache")){

			$cache = $registry->get("cache");
			$routerParams = $cache->fetch("router-params");
		}

		if(empty($routerParams)){

			foreach($this->modules as $module){

				if(!empty($module["Router"]))
					foreach($module["Router"] as $routr){

						$rclass_name = sprintf("%s\Router\%s", $module["base-ns"], $routr);
						$rclass = new \ReflectionClass($rclass_name);
						$parser = new BasicAnnotationParser($rclass);
						$annotations = $parser->getAnnotations();

						foreach($annotations["methods"] as $name=>$items){

							$params = null;
							if(!empty($items)){
								
								$http_method = null;
								if(in_array("item", array_keys($items["Method"])))
									$http_method = $items["Method"]["item"];
								
								if(in_array("items", array_keys($items["Method"])))
									$http_method = implode("-", array_map("trim", $items["Method"]["items"]));
								
								if(is_null($http_method))
									throw new \Exception("StruktFrameworkApplicationException: Unrecorgnized method!");

								$perm = null;
								
								if(in_array("Permission", array_keys($items))){

									if(!is_null($items["Permission"])){

										if(in_array("items", array_keys($items["Permission"])))
											throw new \Exception("PermissionAnnotationException: Accepts only one permission per route!");	

										if(in_array("item", array_keys($items["Permission"])))
											$perm = $items["Permission"]["item"];
									}
								}

								$params["method"] = $http_method;
								$params["route"] = $items["Route"]["item"];
								$params["func"]["class"] = $rclass_name;
								$params["func"]["method"] = $name;
								$params["func"]["permission"] = $perm;
								$routerParams[] = $params;

							}
						}
					}
			}			
		}

		if(!is_null($cache))
			if(!$cache->exists("router-params"))
				$cache->save("router-params", $routerParams);
		
		$object = null;
		foreach($routerParams as $routerParam){

			if(get_class($object) != $routerParam["func"]["class"]){

				$class = new \ReflectionClass($routerParam["func"]["class"]);
				$object = $class->newInstance();
			}

			$func = $class->getMethod($routerParam["func"]["method"])->getClosure($object);

			$router->route($routerParam["method"],
							implode("|", array(

								$routerParam["route"],
								$routerParam["func"]["permission"]
							)),
							$func);
		}

		return $router;
	}

	/**
	* Execute router in debug mode
	*
	* @return void
	*/
	public function runDebug(){

		$this->getRouter()->run();
	}

	/**
	* Execute router
	*
	* @return void
	*/
	public function run(){

		$registry = Registry::getInstance();

		try{

			$this->getRouter()->run();
		}
		catch(\Exception $e){

			if($registry->exists("logger"))
				$registry->get("logger")->error($e);

			$resp = new HtmlFileResponse("../errors/500.html", Response::INTERNALSERVERERROR);
			$resp->output();
		}
	}
}