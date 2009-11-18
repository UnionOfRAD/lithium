<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test\reporter;

use \lithium\test\reporter\Text;

class TextTest extends \lithium\test\Unit {
	
	public function setUp() {
		$this->html = new Text();
	}
	
	public function testFormatWithoutData() {
		$expected = "\n\n";
		$result = $this->html->format(null);
		$this->assertEqual($expected, $result);
	}
	
	public function testFormatGroup() {
		$expected = "-group lithium\n\n";
		$result = $this->html->format('group', array(
			'namespace' => 'lithium', 'name' => 'Lithium', 'menu' => null
		));
		$this->assertEqual($expected, $result);
	}
	
	public function testFormatCase() {
		$expected = "-case lithium\\tests\cases\\test\\reporter\\TextTest\n";
		$result = $this->html->format('case', array(
			'namespace' => 'lithium\tests\cases\test\reporter', 'name' => 'TextTest', 'menu' => null
		));
		$this->assertEqual($expected, $result);
	}
}

?>