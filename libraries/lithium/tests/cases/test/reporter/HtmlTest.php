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

	public function testMenuWithoutData() {
		$expected = '<ul></ul>';
		$result = $this->html->menu(array(), array('format' => 'html'));
		$this->assertEqual($expected, $result);
	}

	public function testFormatGroup() {
		$expected = '<ul><li><a href="?group=\lithium\tests">lithium</a>';
		$expected .= '<ul><li><a href="?group=\lithium\tests\cases">cases</a>';
		$expected .= '<ul><li><a href="?group=\lithium\tests\cases\core">core</a>';
		$expected .= '<ul><li><a href="?case=\lithium\tests\cases\core\LibrariesTest">LibrariesTest</a></li>';
		$expected .= '</ul></li></ul></li></ul></li></ul>';
		$result = $this->html->menu(array('lithium\tests\cases\core\LibrariesTest'), array(
			'format' => 'html', 'tree' => true
		));
		$this->assertEqual($expected, $result);
	}

	public function testFormatCase() {
		$tests = array('\lithium\tests\cases\test\reporter\HtmlTest');
		$expected = '<ul><li><a href="?case=\lithium\tests\cases\test\reporter\HtmlTest">HtmlTest</a></li></ul>';
		$result = $this->html->menu($tests, array('format' => 'html'));
		$this->assertEqual($expected, $result);
	}
}

?>