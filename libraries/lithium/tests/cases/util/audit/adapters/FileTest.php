<?php

namespace lithium\tests\cases\util\audit\adapters;

use \lithium\audit\logger\adapters\File;

class FileTest extends \lithium\test\Unit {

	public function setUp() {
		$this->path = LITHIUM_APP_PATH . '/tmp/logs/';
		$this->Adapter = new File(array('path' => $this->path));
	}

}

?>