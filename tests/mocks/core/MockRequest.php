<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

class MockRequest extends \lithium\core\Object {
	
	public $url = null;

	public function env($key) {
		if(isset($this->_config[$key])) {
			return $this->_config[$key];
		}
		return null;
	}
}

?>