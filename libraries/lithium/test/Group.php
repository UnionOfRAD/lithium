<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use Exception;
use lithium\test\Unit;
use lithium\core\Libraries;
use lithium\util\Collection;

/**
 * A `Collection` of tests that represents a test group.
 *
 * Tests are added to this group either on `construct` by passing a fully-namespaced test class
 * or namespace string-based path, e.g.
 *
 * {{{
 * $group = new Group(array('data' => array(
 *     'data\ModelTest',
 *     new \lithium\tests\cases\core\ObjectTest()
 * )));
 * }}}
 *
 * Or they can be added programmatically:
 *
 * {{{
 * $group->add('data\ModelTest');
 * }}}
 */
class Group extends \lithium\util\Collection {

	/**
	 * auto init for setting up items passed into constructor
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		$data = $this->_data;
		$this->_data = array();

		foreach ($data as $item) {
			$this->add($item);
		}
	}

	/**
	 * Get all test cases. By default, does not include function or integration tests.
	 *
	 * @param string $options
	 * @return array
	 */
	public static function all(array $options = array()) {
		$defaults = array(
			'library' => true,
			'filter' => '/cases/',
			'exclude' => '/mock/',
			'recursive' => true,
		);
		$options += $defaults;
		$classes = Libraries::locate('tests', null, $options);
		return $classes;
	}

	/**
	 * Add a tests to the group.
	 *
	 * @param string $test The test to be added.
	 * @param string $options Method options. Currently not used in this method.
	 * @return array Updated list of tests contained within this collection.
	 */
	public function add($test = null, array $options = array()) {
		$resolve = function($self, $test) {
			switch (true) {
				case !$test:
					return array();
				case is_object($test) && $test instanceof Unit:
					return array(get_class($test));
				case is_string($test) && !file_exists(Libraries::path($test)):
					return $self->invokeMethod('_unitClass', array($test));
				default:
					return (array) $test;
			}
		};
		if (is_array($test)) {
			foreach ($test as $t) {
				$this->_data = array_filter(array_merge($this->_data, $resolve($this, $t)));
			}
			return $this->_data;
		}
		return $this->_data = array_merge($this->_data, $resolve($this, $test));
	}

	/**
	 * Get the collection of tests
	 *
	 * @param string $params
	 * @param string $options
	 * @return lithium\util\Collection
	 */
	public function tests($params = array(), array $options = array()) {
		$tests = new Collection();

		foreach ($this->_data as $test) {
			if (!class_exists($test)) {
				throw new Exception("Test case '{$test}' not found.");
			}
			$tests[] = new $test;
		}
		return $tests;
	}

	/**
	 * Gets a unit test class (or classes) from a class or namespace path string.
	 *
	 * @param string $test The path string in which to find the test case(s). This may be a
	 *               namespace, a Lithium package name, or a fully-namespaced class reference.
	 * @return array Returns an array containing one or more fully-namespaced class references to
	 *         unit tests.
	 */
	protected function _unitClass($test) {
		if ($test[0] != '\\' && strpos($test, 'lithium\\') === false) {
			if (file_exists(Libraries::path($test = "lithium\\tests\cases\\{$test}"))) {
				return array($test);
			}
		}
		if (preg_match("/Test/", $test)) {
			return array($test);
		}
		if (!$test = trim($test, '\\')) {
			return array();
		}
		list($library, $path) = explode('\\', $test, 2) + array($test, null);

		return (array) Libraries::find($library, array(
			'recursive' => true,
			'path' => '/' . str_replace('\\', '/', $path),
			'filter' => '/cases|integration|functional/',
		));
	}
}

?>