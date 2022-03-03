<?php

use PHPUnit\Framework\TestCase;
use Strukt\Core\Registry;
use Strukt\Framework\Provider\Validator as ValidatorService;

class FormValidatorTest extends TestCase{

	public function setUp():void{

		$factory = new ValidatorService(); 
		$factory->register();

		$registry = Registry::getSingleton();

		$this->service = $registry->get("strukt.service.validator");
	}

	/**
     * @runInSeparateProcess
     */
	public function testLen(){

		$validator = $this->service->getNew("Moderator")
							->isLenGt(8) //App\Validator\Extra
							->isLen(9)
							->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_valid_length"=>true,
			"is_not_empty"=>true,
			"is_gt"=>true
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testIsLenFail(){

		$validator = $this->service->getNew("Moderator")
					->isLen(45)
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_valid_length"=>false,
			"is_not_empty"=>true
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testIsAlpha(){

		$validator = $this->service->getNew("moderator")
					->isAlpha()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alpha"=>true,
			"is_not_empty"=>true
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testIsAlphaSpaced(){

		$validator = $this->service->getNew("pitsolu moderator")
					->isAlpha()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alpha"=>true,
			"is_not_empty"=>true
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testIsAlphaNum(){

		$validator = $this->service->getNew("pa55w0rd")
					->isAlphaNum()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alphanum"=>true,
			"is_not_empty"=>true
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testIsAlphaNumFail(){

		$validator = $this->service->getNew("p@55w0rd")
					->isAlphaNum()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_alphanum"=>false,
			"is_not_empty"=>true
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testIsEmail(){

		$validator = $this->service->getNew("pitsolu@gmail.com")
					->isEmail()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_email"=>true,
			"is_not_empty"=>true
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testIsDate(){

		$date = new DateTime("now");

		$validator = $this->service->getNew($date->format("Y-m-d"))
					->isDate()
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_date"=>true,
			"is_not_empty"=>true
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testIsIn(){

		$validator = $this->service->getNew("Ron")
					->isIn(array("Obama", "Romney", "Hilary", "Ron"))
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"is_not_empty"=>true,
			"in_enum"=>true
		));
	}

	/**
     * @runInSeparateProcess
     */
	public function testEqualsTo(){

		$validator = $this->service->getNew(sha1("p@55w0rd"))
					->equalTo(sha1("p@55w0rd"))
					->isNotEmpty();

		$this->assertEquals($validator->getMessage(), array(

			"equal_to"=>true,
			"is_not_empty"=>true
		));
	}
}