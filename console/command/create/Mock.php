<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console\command\create;

use lithium\util\Inflector;

/**
 * Generate a Mock that extends the name of the given class in the `--library` namespace.
 *
 * `li3 create mock model Posts`
 * `li3 create --library=li3_plugin mock model Posts`
 *
 */
class Mock extends \lithium\console\command\Create {

	/**
	 * Get the namespace for the mock.
	 *
	 * @param string $request
	 * @param array|string $options
	 * @return string
	 */
	protected function _namespace($request, $options = []) {
		$request->params['command'] = $request->action;
		return parent::_namespace($request, ['prepend' => 'tests.mocks.']);
	}

	/**
	 * Get the parent for the mock.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _parent($request) {
		$namespace = parent::_namespace($request);
		$class = Inflector::camelize($request->action);
		return "\\{$namespace}\\{$class}";
	}

	/**
	 * Get the class name for the mock.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _class($request) {
		$type = $request->action;
		$name = $request->args();

		if ($command = $this->_instance($type)) {
			$request->params['action'] = $name;
			$name = $command->invokeMethod('_class', [$request]);
		}
		return "Mock{$name}";
	}

	/**
	 * Get the methods for the mock to override
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _methods($request) {
		return null;
	}
}

?>