<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
 * ```
 * $group = new Group(['data' => [
 *     'data\ModelTest',
 *     new \lithium\tests\cases\core\LibrariesTest()
 * ]]);
 * ```
 *
 * Or they can be added programmatically:
 *
 * ```
 * $group->add('data\ModelTest');
 * ```
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
		$this->_data = [];

		foreach ($data as $item) {
			$this->add($item);
		}
	}

	/**
	 * Get all test cases. By default, does not include function or integration tests.
	 *
	 * @param array $options
	 * @return array
	 */
	public static function all(array $options = []) {
		$defaults = [
			'filter' => '/cases/',
			'exclude' => '/mocks/',
			'recursive' => true
		];
		return Libraries::locate('tests', null, $options + $defaults);
	}

	/**
	 * Add a tests to the group.
	 *
	 * @param string $test The test to be added.
	 * @param array $options Method options. Currently not used in this method.
	 * @return array Updated list of tests contained within this collection.
	 */
	public function add($test = null, array $options = []) {
		$resolve = function($test) {
			switch (true) {
				case !$test:
					return [];
				case is_object($test) && $test instanceof Unit:
					return [get_class($test)];
				case is_string($test) && !file_exists(Libraries::path($test) ?? ""):
					return $this->_resolve($test);
				default:
					return (array) $test;
			}
		};
		if (is_array($test)) {
			foreach ($test as $t) {
				$this->_data = array_filter(array_merge($this->_data, $resolve($t)));
			}
			return $this->_data;
		}
		return $this->_data = array_merge($this->_data, $resolve($test));
	}

	/**
	 * Get the collection of tests
	 *
	 * @param string|array $params
	 * @param array $options
	 * @return lithium\util\Collection
	 */
	public function tests($params = [], array $options = []) {
		$tests = new Collection();

		foreach ($this->_data as $test) {
			if (!class_exists($test)) {
				throw new Exception("Test case `{$test}` not found.");
			}
			$tests[] = new $test;
		}
		return $tests;
	}

	/**
	 * Resolves a unit test class (or classes) from a class or namespace path string.
	 *
	 * @param string $test The path string in which to find the test case(s). This may be a
	 *               library, a namespace, or a fully-namespaced class reference.
	 * @return array Returns an array containing one or more fully-namespaced class references to
	 *               unit tests.
	 */
	protected function _resolve($test) {
		if (strpos($test, '\\') === false && Libraries::get($test)) {
			return (array) Libraries::find($test, [
				'recursive' => true,
				'filter' => '/(cases|integration|functional)\\\.*Test$/',
				'exclude' => '/tests\\\mocks/'
			]);
		}
		if (!$test = trim($test, '\\')) {
			return [];
		}
		list($library, $path) = explode('\\', $test, 2) + [$test, null];

		return (array) Libraries::find($library, [
			'recursive' => true,
			'path' => '/' . str_replace('\\', '/', $path ?? ""),
			'filter' => '/(cases|integration|functional)\\\.*Test$/',
			'exclude' => strstr($test, 'tests\mocks') ? '' : '/tests\\\mocks/'
		]);
	}
}

?>