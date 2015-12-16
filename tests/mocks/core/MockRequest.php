<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\core;

use lithium\core\Filterable;

class MockRequest extends \lithium\core\Object {
	use Filterable;

	public $url = null;

	public $params = array();

	public $argv = array();

	public function __get($key) {
		if (isset($this->params[$key])) {
			return $this->params[$key];
		}
		return null;
	}

	public function env($key) {
		if (isset($this->_config[$key])) {
			return $this->_config[$key];
		}
		return null;
	}

	public function get($key) {
		return $this->env($key);
	}
}

?>