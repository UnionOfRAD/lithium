<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\util\audit\logger\adapter;

use \lithium\audit\logger\adapter\File;

class FileTest extends \lithium\test\Unit {

	public function setUp() {
		$this->path = LITHIUM_APP_PATH . '/resources/tmp/logs/';
		$this->Adapter = new File(array('path' => $this->path));
	}
}

?>