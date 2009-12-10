<?php

namespace lithium\tests\cases\util\audit\logger\adapter;

use \lithium\audit\logger\adapter\File;

class FileTest extends \lithium\test\Unit {

	public function setUp() {
		$this->path = LITHIUM_APP_PATH . '/resources/tmp/logs/';
		$this->Adapter = new File(array('path' => $this->path));
	}
}

?>