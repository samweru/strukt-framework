<?php

use PHPUnit\Framework\TestCase;
use Strukt\Application;
use Strukt\Router\Kernel as Router;
use Strukt\Core\Collection;
use Strukt\Core\Map;
use Strukt\Core\Registry;
use Strukt\Http\Request;
use Strukt\Fs;

class ApplicationTest extends TestCase{

	public function setUp():void{

		$app_cfg = parse_ini_file("cfg/app.ini");

		$this->app_name = $app_cfg["app-name"];
		$this->auth_mod_name = sprintf("%sAuthModule", $app_cfg["app-name"]);
		$this->auth_mod_ns = sprintf("%s\AuthModule\%s", $this->app_name, $this->auth_mod_name);
		$this->auth_mod_path = sprintf("%s/app/src/%s/AuthModule", realpath("."), $this->app_name);
		$this->auth_mod_base_ns = sprintf("%s\AuthModule", $this->app_name);
	}

	public function testModuleOutput(){
		
		$ref_cls = new ReflectionClass($this->auth_mod_ns);
		$auth_mod = $ref_cls->newInstance();

		$this->assertEquals($auth_mod->getAlias(), "Au");
		$this->assertEquals($auth_mod->getNamespace(), $this->auth_mod_ns);
		
		if(!Fs::isWindows())
			$this->assertEquals($auth_mod->getBaseDir(), $this->auth_mod_path);

		return $auth_mod;
	}

	/**
	* @depends testModuleOutput
	*/
	public function testNameRegistryOutput($auth_mod){

		$app = new Application(new Router(Request::createFromGlobals()));
		$app->register($auth_mod);
		$app->run()->init();

		$nr = Registry::getSingleton()->get("nr");

		$this->assertInstanceOf(Map::class, $nr);
		$this->assertEquals($nr->get("au.name"), "AuthModule");
		$this->assertEquals($nr->get("au.fname"), $this->auth_mod_name);
		$this->assertEquals($nr->get("au.app.name"), $this->app_name);
		$this->assertEquals($nr->get("au.ns"), $this->auth_mod_ns);
		$this->assertEquals($nr->get("au.base.ns"), sprintf("%s\AuthModule", $this->app_name));

		$auth_rtr = $nr->get("au.rtr");

		$rtr = new Collection($auth_rtr->getName());
		$rtr->set("Auth", sprintf("%s\Router\Auth", $this->auth_mod_base_ns));
		$rtr->set("Index", sprintf("%s\Router\Index", $this->auth_mod_base_ns));

		$this->assertEquals($auth_rtr, $rtr);
	}

	/**
	* @depends testNameRegistryOutput
	*/
	public function testModuleCoreStaticClass(){

		$core = Registry::getSingleton()->get("core");

		$user_ctr = $core->get("au.ctr.User");

		$this->assertTrue($user_ctr->doAuth("admin","p@55w0rd"));
	}
}