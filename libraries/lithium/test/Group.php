<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \lithium\core\Libraries;
use \lithium\util\Inflector;
use \lithium\util\Collection;

class Group extends \lithium\util\Collection {

	protected function _init() {
		parent::_init();

		$items = $this->_items;
		$this->_items = array();

		foreach ($items as $item) {
			$this->add($item);
		}
	}

	public static function all($options = array()) {
		$defaults = array('transform' => false, 'library' => true);
		$options += $defaults;

		$m = '/\\\\tests\\\\cases\\\\(.+)Test$/';
		$filter = function($class) use ($m) { return preg_replace($m, '\\\\\1', $class); };
		$classes = Libraries::find($options['library'], array(
			'filter' => '/\w+Test$/', 'recursive' => true
		));
		return $options['transform'] ? array_map($filter, $classes) : $classes;
	}

	public function add($test = null, $options = array()) {
		$callback = function($test) {
			if (empty($test)) {
				return array();
			}

			if (is_object($test) && $test instanceof \lithium\test\Unit) {
				$test = get_class($test);
			} elseif (is_string($test) && $test[0] == '\\') {
				$test = Libraries::find(true, array(
					'recursive' => true,
					'path' => '/tests/cases' . str_replace("\\", "/", $test)
				));
			} elseif (is_string($test)) {
				$test = array("lithium\\tests\cases\\$test");
			}
			return (array)$test;
		};

		if (is_array($test)) {
			foreach ($test as $t) {
				$this->_items = array_filter(array_merge($this->_items, $callback($t)));
			}
			return $this->_items;
		}
		return $this->_items = array_merge($this->_items, $callback($test));
	}

	public function tests($params = array(), $options = array()) {
		$tests = new Collection();
		array_map(function($test) use ($tests) { $tests[] = new $test; }, $this->_items);
		return $tests;
	}
}

?>