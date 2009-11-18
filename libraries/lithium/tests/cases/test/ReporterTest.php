<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test;

use \lithium\test\Reporter;

class ReporterTest extends \lithium\test\Unit {
	
	public function testMenu() {
		$expected = "\n-case lithium\core\Object\n-case lithium\core\Libraries\n\n";
		$result = Reporter::menu(array('lithium\core\Object', 'lithium\core\Libraries'));
		$this->assertEqual($expected, $result);
	}
}

?>