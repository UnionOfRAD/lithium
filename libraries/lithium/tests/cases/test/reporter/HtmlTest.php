<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\test\reporter;

use \lithium\test\reporter\Html;

class HtmlTest extends \lithium\test\Unit {
	
	public function setUp() {
		$this->html = new Html();
	}
	
	public function testFormatWithoutData() {
		$expected = '<ul></ul>';
		$result = $this->html->format(null);
		$this->assertEqual($expected, $result);
	}
	
	public function testFormatGroup() {
		$expected = '<li><a href="?group=lithium">Lithium</a></li>';
		$result = Html::format('group', array(
			'namespace' => 'lithium', 'name' => 'Lithium', 'menu' => null
		));
		$this->assertEqual($expected, $result);
	}
	
	public function testFormatCase() {
		$expected = '<li><a href="?case=lithium\tests\cases\test\reporter\HtmlTest">HtmlTest</a></li>';
		$result = $this->html->format('case', array(
			'namespace' => 'lithium\tests\cases\test\reporter', 'name' => 'HtmlTest', 'menu' => null
		));
		$this->assertEqual($expected, $result);
	}
}

?>