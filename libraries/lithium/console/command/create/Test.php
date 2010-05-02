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

	protected function _namespace($request, $options = array()) {
		$request->shift();
		return parent::_namespace($request, array('prepend' => 'tests.cases.'));
	}

	protected function _use($request) {
		$namespace = parent::_namespace($request);
		$class = $request->action;
		return "\\{$namespace}\\{$class}";
	}

	protected function _class($request) {
		$name = $request->action;
		$type = $request->command;

		if ($command = $this->{$type}) {
			$request->params['action'] = $name;
			$name = $command->invokeMethod('_class', array($request));
		}
		return  Inflector::classify("{$name}Test");
	}

	protected function _methods($request) {
		$use = $this->_use($request);
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