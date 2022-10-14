<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console\command\create;

use lithium\core\Libraries;
use lithium\util\Inflector;
use lithium\analysis\Inspector;
use lithium\core\ClassNotFoundException;

/**
 * Generate a Test class in the `--library` namespace
 *
 * `li3 create test model Posts`
 * `li3 create --library=li3_plugin test model Posts`
 *
 */
class Test extends \lithium\console\command\Create {

	/**
	 * Get the namespace for the test case.
	 *
	 * @param object $request
	 * @param array $options
	 * @return string
	 */
	protected function _namespace($request, $options = []) {
		$request->params['command'] = $request->action;
		return parent::_namespace($request, ['prepend' => 'tests.cases.']);
	}

	/**
	 * Get the class used by the test case.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _use($request) {
		return parent::_namespace($request) . '\\' . $this->_name($request);
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
		$path = Libraries::path($use) ?? "";

		if (!file_exists($path)) {
			return "";
		}
		$methods = (array) Inspector::methods($use, 'extents');
		$testMethods = [];

		foreach (array_keys($methods) as $method) {
			$testMethods[] = "\tpublic function test" . ucwords($method) . "() {}";
		}
		return join("\n", $testMethods);
	}

	/**
	 * Get the class to be tested
	 *
	 * @param object $request
	 * @return string
	 */
	protected function _name($request) {
		$type = $request->action;
		$name = $request->args();

		try {
			$command = $this->_instance($type);
		} catch (ClassNotFoundException $e) {
			$command = null;
		}

		if ($command) {
			$request->params['action'] = $name;
			$name = $command->invokeMethod('_class', [$request]);
		}
		$request->params['action'] = $type;
		return $name;
	}
}

?>