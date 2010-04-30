<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\create;

use \lithium\core\Libraries;
use \lithium\util\Inflector;
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

	protected function _namespace($name = null, $options = array()) {
		return parent::_namespace($this->request->action, array('prepend' => 'tests.cases.'));
	}

	protected function _use() {
		$namespace = parent::_namespace($this->request->action);
		$class = $this->request->args(0);
		return "\\{$namespace}\\{$class}";
	}

	protected function _class() {
		$name = $this->request->args(0);
		$type = $this->request->params['action'];
		$this->request->params['action'] = $name;

		if ($command = $this->{$type}) {
			$name = $command->invokeMethod('_class');
		}
		$this->request->params['action'] = $type;
		return  Inflector::classify("{$name}Test");
	}

	protected function _methods() {
		$use = $this->_use();
		$path = Libraries::path($use);

		if (!file_exists($path)) {
			return "";
		}
		$methods = (array) Inspector::methods($use, 'extents');
		$testMethods = array();

		foreach (array_keys($methods) as $method) {
			$testMethods[] = "\tpublic function test" . ucwords($method) . "() {}";
		}
		return join("\n", $testMethods);
	}
}

?>