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

	protected function _namespace($name = null, $options = array()) {
		return parent::_namespace($this->request->action, array('prepend' => 'tests.mocks.'));
	}

	protected function _parent() {
		$namespace = parent::_namespace($this->request->action);
		$class = $this->request->args(0);
		return "\\{$namespace}\\{$class}";
	}

	protected function _class() {
		$class = $this->request->args(0);
		return "Mock{$class}";
	}

	protected function _methods() {
		return null;
	}
}

?>