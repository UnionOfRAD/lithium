<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\create;

use lithium\core\Libraries;
use lithium\util\Inflector;
use lithium\analysis\Inspector;

/**
 * Generate a Test class in the `--library` namespace
 *
 * `li3 create test model Post`
 * `li3 create --library=li3_plugin test model Post`
 *
 */
class Test extends \lithium\console\command\Create {

    /**
     * Get the namespace for the test case.
     *
     * @param string $request
     * @param array $options
     * @return string
     */
	protected function _namespace($request, $options = array()) {
		$request->params['command'] = $request->action;
		return parent::_namespace($request, array('prepend' => 'tests.cases.'));
	}

    /**
     * Get the class used by the test case.
     *
     * @param string $request
     * @return string
     */
	protected function _use($request) {
		$namespace = parent::_namespace($request);
		$name = $this->_name($request);
		return "\\{$namespace}\\{$name}";
	}

    /**
     * Get the class name for the test case.
     *
     * @param string $request
     * @return string
     */
	protected function _class($request) {
		$name = $this->_name($request);
		return  Inflector::classify("{$name}Test");
	}

    /**
     * Get the methods to test.
     *
     * @param string $request
     * @return string
     */
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

	/**
	 * Get the class to be tested
	 *
	 * @param string $request
     * @return string
	 */
	protected function _name($request) {
		$type = $request->action;
		$name = $request->args();

		if ($command = $this->_instance($type)) {
			$request->params['action'] = $name;
			$name = $command->invokeMethod('_class', array($request));
		}
		$request->params['action'] = $type;
		return $name;
	}
}

?>