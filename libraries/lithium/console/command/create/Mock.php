<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\create;

use \lithium\core\Libraries;

/**
 * Generate a Mock that extends the name of the given class in the given namespace.
 * `li3 create mock model Post`
 * `li3 create --library=li3_plugin mock model Post`
 *
 * @param string $type namespace of the class (e.g. model, controller, some.name.space).
 * @param string $name Class name to extend with the mock.
 * @return void
 */
class Mock extends \lithium\console\command\Create {

	protected function _namespace($request, $options = array()) {
		$request->shift();
		return parent::_namespace($request, array('prepend' => 'tests.mocks.'));
	}

	protected function _parent($request) {
		$namespace = parent::_namespace($request);
		$class = $request->action;
		return "\\{$namespace}\\{$class}";
	}

	protected function _class($request) {
		$class = $request->action;
		return "Mock{$class}";
	}

	protected function _methods($request) {
		return null;
	}
}

?>