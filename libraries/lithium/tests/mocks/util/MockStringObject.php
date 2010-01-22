<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\util;

class MockStringObject extends \lithium\template\view\Renderer {

	public function render($template, $data = array(), $options = array()) {
	}

	public function __toString() {
		return 'custom object';
	}
}

?>