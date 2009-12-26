<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\build;

use \lithium\core\Libraries;

/**
 * Builds Test cases
 *
 * @package default
 */
class Test extends \lithium\console\command\Build {

	public function interactive() {

	}

	public function __call($type, $params) {
		$library = Libraries::get($this->library);
		if (empty($library['prefix'])) {
			return false;
		}
		$namespace = $this->_namespace($type);
		$name = array_shift($params);
		$use = "\\{$library['prefix']}{$namespace}\\{$name}";
		$methods =  array();

		if (class_exists($use)) {
			$methods = array();
			foreach ((array) get_class_methods($use) as $method) {
				$methods[] = "\tpublic function test" . ucwords($method)."() {}";
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

	public function mock($type = null, $name) {
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
}

?>