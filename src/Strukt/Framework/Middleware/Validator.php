<?php

namespace Strukt\Framework\Middleware;

use Strukt\Contract\Http\RequestInterface;
use Strukt\Contract\Http\ResponseInterface;
use Strukt\Contract\MiddlewareInterface;
use Strukt\Http\Error\BadRequest;
use Strukt\Cmd;

/**
* @Name(valid)
*/
class Validator implements MiddlewareInterface{

	public function __construct(){

		//
	}

	public function __invoke(RequestInterface $request, 
								ResponseInterface $response, callable $next){

		$action = $request->getMethod(); 

		$headers = [];
		if(env("json.validation.err"))
			$headers = ["Content-Type"=>"application/json"];

		$route = reg("route.current");
		$configs = reg("route.configs");

		// dd(reg("route.configs"));
		// dd(reg("@strukt.permissions"));
		
		$name = sprintf("type:route|path:%s|action:%s", $route, $request->getMethod());

		if(array_key_exists($name, $configs)){
		
			$tokq = token($configs[$name]);

			if($tokq->has("form")){	

				if($action == "OPTIONS" &&  config("app.type") == "App:Idx"){

					$body = json($request->getContent())->decode();
					foreach($body as $name=>$val)
						$request->request->set($name, $val);
				}

				$class = reg(sprintf("nr.%s.frm.%s", $tokq->get("module"), $tokq->get("form")));

				$messages = \Strukt\Ref::create($class)->makeArgs([$request])->method("validate")->invoke();
				if(!$messages["success"])
					$response = new BadRequest(json($messages)->encode(), $headers);
			}
		}
	
		return $next($request, $response);
	}
}