<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\create;

use \lithium\core\Libraries;
use \lithium\analysis\Inspector;

/**
 * Generate test cases in the given namespace.
 * `li3 create test model Post`
 * `li3 create test --library=li3_plugin model Post`
 *
 * @param string $type namespace of the class (e.g. model, controller, some.name.space).
 * @param string $name Name of class to test.
 * @return void
 */
class Test extends \lithium\console\command\Create {

	protected function _namespace($name = null) {
		return parent::_namespace($name) . "\\{$this->request->action}";
	}

	protected function _use() {
		$namespace = $this->_namespace($this->request->command);
		$class = array_shift($this->request->params['args']);
		return "\\{$namespace}\\{$class}";
	}

	protected function _class() {
		$class = array_shift($this->request->params['args']);
		return  $class . "Test";
	}

	protected function _methods() {
		$use = $this->_use();

		if (!class_exists($use, false)) {
			return "";
		}
		$methods = array();

		foreach (array_keys(Inspector::methods($use, 'extents')) as $method) {
			$methods[] = "\tpublic function test" . ucwords($method) . "() {}";
		}
		return join("\n", $methods);
	}
}

?>