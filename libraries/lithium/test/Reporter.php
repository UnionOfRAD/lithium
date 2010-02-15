<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \Exception;
use \lithium\core\Libraries;
use \lithium\util\Inflector;

/**
 * Reporter class to handle test report output.
 */
class Reporter extends \lithium\core\Object {

	public function stats($stats) {
		$defaults = array(
			'asserts' => null,
			'passes' => array(),
			'fails' => array(),
			'errors' => array(),
			'exceptions' => array(),
			'skips' => array()
		);
		$stats = (array) $stats + $defaults;

		$count = array_map(
			function($value) { return is_array($value) ? count($value) : $value; },
			$stats
		);
		$success = $count['passes'] === $count['asserts'] && $count['errors'] === 0;

		$result[] = $this->_result($count + compact('success'));

		foreach ((array) $stats['errors'] as $error) {
			$error = array_merge(
				array('class' => 'unknown', 'method' => 'unknown'), (array) $error
			);
			$method = "_{$error['result']}";
			$result[] = $this->{$method}($error);
		}
		foreach ((array) $stats['skips'] as $skip) {
			$result[] = $this->_skip($skip);
		}
		return trim(join("\n", $result));
	}

	/**
	 * Return menu as a string to be used as render.
	 *
	 * @param array $classes
	 * @param array $options
	 *               - format: type of reporter class. eg: html default: text
	 *               - tree: true to convert classes to tree structure
	 */
	public function menu($classes, $options = array()) {
		$defaults = array('request' => null, 'tree' => false);
		$options += $defaults;

		if ($options['tree']) {
			$data = array();
			$assign = function(&$data, $class, $i = 0) use (&$assign) {
				isset($data[$class[$i]]) ?: $data[] = $class[$i];
				$end = (count($class) <= $i + 1);

				if (!$end && ($offset = array_search($class[$i], $data)) !== false) {
					$data[$class[$i]] = array();
					unset($data[$offset]);
				}
				ksort($data);
				$end ?: $assign($data[$class[$i]], $class, $i + 1);
			};

			foreach ($classes as $class) {
				$assign($data, explode('\\', str_replace('\tests', '', $class)));
			}
			$classes = $data;
		}
		ksort($classes);

		$result = null;

		if ($options['tree']) {
			$self = $this;
			$menu = function ($data, $parent = null) use (&$menu, &$self, $result, $options) {
				foreach ($data as $key => $row) {
					if (is_array($row) && is_string($key)) {
						$key = strtolower($key);
						$next = $parent . '/' . $key;
						$result .= $self->invokeMethod('_item', array('group', array(
							'request' => $options['request'], 'namespace' => $next,
							'name' => $key, 'menu' => $menu($row, $next)
						)));
					} else {
						$next = $parent . '/' . $key;
						$result .= $self->invokeMethod('_item', array('case', array(
							'request' => $options['request'], 'namespace' => $parent, 'name' => $row,
						)));
					}
				}
				return $self->invokeMethod('_item', array(null, array('menu' => $result)));
			};

			foreach ($classes as $library => $tests) {
				$group = "{$library}/tests";
				$result .= $this->_item(null, array('menu' => $this->_item('group', array(
					'request' => $options['request'], 'namespace' => $group,
					'name' => $library, 'menu' => $menu($tests, $group)
				))));
			}
			return $result;
		}

		foreach ($classes as $test) {
			$parts = explode('\\', $test);
			$result .= $this->_item('case', array(
				'request' => $options['request'], 'name' => array_pop($parts),
				'namespace' => join('/', $parts)
			));
		}
		return $this->_item(null, array('menu' => $result));
	}

	public function filters($filters) {}

	protected function _result($data) {}

	protected function _fail($data) {}

	protected function _exception($data) {}

	protected function _skip($data) {}

	protected function _item($data) {}
}

?>