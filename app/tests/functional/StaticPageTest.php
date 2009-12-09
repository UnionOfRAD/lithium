<?php

namespace app\tests\functional;

use \lithium\action\Request;
use \lithium\action\Dispatcher;
use \app\controllers\PagesController;

class StaticPageTest extends \lithium\test\Unit {

	public function testRendingHome() {
		$_GET['url'] =  '/';
		$result = Dispatcher::run(new Request(array(
			'controller' => 'pages',
			'action' => 'view',
			'args' => array()
		)));
		$this->assertFalse($result === null);
		$this->assertTrue(is_string($result->body[0]));
	}

}

?>