<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \lithium\core\Libraries;
use \lithium\util\Collection;

/**
 * Group Test Collection
 *
 * @package lithium.test
 */
class Group extends \lithium\util\Collection {

	/**
	 * auto init for setting up items passed into constructor
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		$items = $this->_items;
		$this->_items = array();
		foreach ($items as $item) {
			$this->add($item);
		}
	}

	/**
	 * Get all tests
	 *
	 * @param string $options
	 * @return array
	 */
	public static function all($options = array()) {
		$defaults = array('transform' => false, 'library' => true);
		$options += $defaults;
		$m = '/\\\\tests\\\\cases\\\\(.+)Test$/';
		$transform = function($class) use ($m) { return preg_replace($m, '\\\\\1', $class); };
		$classes = Libraries::locate('tests', null, $options + array(
			'filter' => '/cases|integration|functional/', 'recursive' => true
		));
		return $options['transform'] ? array_map($transform, $classes) : $classes;
	}

	/**
	 * Add a tests to the group
	 *
	 * @param string $test
	 * @param string $options
	 * @return array
	 */
	public function add($test = null, $options = array()) {
		$callback = function($test) {
			if (empty($test)) {
				return array();
			}
			if (is_object($test) && $test instanceof \lithium\test\Unit) {
				return array(get_class($test));
			}
			if (is_string($test)) {
				if ($test[0] != '\\') {
					$test = "lithium\\tests\cases\\{$test}";
				}
				if (preg_match("/Test/", $test)) {
					return array($test);
				}
				$parts = array_filter(explode("\\", $test));
				$library = array_shift($parts);
				$test = Libraries::find($library, array(
					'recursive' => true,
					'path' => '/' . join('/', $parts),
					'filter' => '/cases|intergration|functional/'
				));
				return (array) $test;
			}
			return (array) $test;
		};

		if (is_array($test)) {
			foreach ($test as $t) {
				$this->_items = array_filter(array_merge($this->_items, $callback($t)));
			}
			return $this->_items;
		}
		return $this->_items = array_merge($this->_items, $callback($test));
	}

	/**
	 * Get the collection of tests
	 *
	 * @param string $params
	 * @param string $options
	 * @return lithium\util\Collection
	 */
	public function tests($params = array(), $options = array()) {
		$tests = new Collection();
		array_map(function($test) use ($tests) { $tests[] = new $test; }, $this->_items);
		return $tests;
	}
}

?>