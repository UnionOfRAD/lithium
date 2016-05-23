<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\util;

class MockTextObject extends \lithium\template\view\Renderer {

	public $message = 'custom object';

	public function render($template, $data = array(), array $options = array()) {
	}

	public function __toString() {
		return $this->message;
	}
}

?>