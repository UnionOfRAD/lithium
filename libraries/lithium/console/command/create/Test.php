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
 * Create test cases or mocks in a namespace for a class.
 *
 */
class Test extends \lithium\console\command\Create {

	/**
	 * Generate test cases in the given namespace.
	 * `li3 create test model Post`
	 * `li3 create test --library=li3_plugin model Post`
	 *
	 * @param string $type namespace of the class (e.g. model, controller, some.name.space).
	 * @param string $name Name of class to test.
	 * @return void
	 */
	public function run($type = null, $name = null) {
		$library = Libraries::get($this->library);
		if (empty($library['prefix'])) {
			return false;
		}
		$namespace = $this->_namespace($type);
		$use = "\\{$library['prefix']}{$namespace}\\{$name}";
		$methods =  array();

		if (class_exists($use, false)) {
			$methods = array();
			foreach (array_keys(Inspector::methods($use, 'extents')) as $method) {
				$methods[] = "\tpublic function test" . ucwords($method) . "() {}";
			}
		}
		$params = array(
			'namespace' => "{$library['prefix']}tests\\cases\\{$namespace}",
			'use' => $use,
			'class' => "{$name}Test",
			'methods' => join("\n", $methods),
		);

		if ($this->_save($this->template, $params)) {
			$this->out(
				"{$params['class']} created for {$name} in {$params['namespace']}."
			);
			return true;
		}
		return false;
	}

	/**
	 * Generate a Mock that extends the name of the given class in the given namespace.
	 * `li3 create test mock model Post`
	 * `li3 create test --library=li3_plugin mock model Post`
	 *
	 * @param string $type namespace of the class (e.g. model, controller, some.name.space).
	 * @param string $name Class name to extend with the mock.
	 * @return void
	 */
	public function mock($type = null, $name = null) {
		$library = Libraries::get($this->library);
		if (empty($library['prefix'])) {
			return false;
		}
		$namespace = $this->_namespace($type);
		$params = array(
			'namespace' => "{$library['prefix']}tests\\mocks\\{$namespace}",
			'class' => "Mock{$name}",
			'parent' => "\\{$library['prefix']}{$namespace}\\{$name}",
			'methods' => null
		);
		if ($this->_save('mock', $params)) {
			$this->out("{$params['class']} created for {$name} in {$params['namespace']}.");
			return true;
		}
		return false;
	}

	public function interactive() {

	}
}

?>