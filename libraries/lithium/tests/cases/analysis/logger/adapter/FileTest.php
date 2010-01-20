<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\analysis\logger\adapter;

use \lithium\analysis\logger\adapter\File;

class FileTest extends \lithium\test\Unit {

	public function setUp() {
		die('WTF?');
		$this->path = LITHIUM_APP_PATH . '/resources/tmp/logs/';
		$this->Adapter = new File(array('path' => $this->path));
	}
}

?>