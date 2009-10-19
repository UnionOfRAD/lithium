<?php

namespace app\controllers;

class HelloWorldController extends \lithium\action\Controller {

	public function index() {
		$this->render(array('layout' => false));
	}

	public function to_string() {
		return "Hello World";
	}

	public function to_json() {
		$this->render(array('json' => 'Hello World'));
	}
}

?>