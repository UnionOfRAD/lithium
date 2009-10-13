<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \lithium\util\Set;
use \lithium\util\Inflector;
use \lithium\core\Libraries;

class Dispatcher extends \lithium\core\StaticObject {

	protected static $_classes = array(
		'group' => '\lithium\test\Group'
	);

	public static function run($group = null, $options = array()) {
		$default = array(
			'base' => null,
			'case' => null,
			'group' => null,
			'filters' => array(),
			'path' => LITHIUM_LIBRARY_PATH . '/lithium/tests/cases',
		);
		$options += $default;
		$group = $group ?: static::_group($options);

		if (!$group) {
			return null;
		}
		$title = $options['case'] ?: '\lithium\tests\cases'. $options['group'];
		list($results, $filters) = static::_execute($group, Set::normalize($options['filters']));

		if (is_null($options['base'])) {
			$options['base'] = $options['path'];
		}

		return compact('title', 'results', 'filters');
	}

	public static function menu($type, $data = null, $parent = null) {
		if ($data == null) {
			$tests = function($callback, $path, $base = null) {
				$name = str_replace($base .'/', '', basename($path, '.php'));
				if (is_dir($path)) {
					$paths = glob($path . '/*');
					if (!empty($paths)) {
						$results = array();
						$count = count($paths);
						$results[$name] = array_filter(array_map(
							$callback,
							array_pad(array(), $count, $callback),
							$paths,
							array_pad(array(), $count, $path)
						));
						if ($base == null) {
							return $results[$name];
						}
						return $results;
					}
				}
				return $name;
			};
			$data = $tests($tests, LITHIUM_LIBRARY_PATH . '/lithium/tests/cases');
		}
		$result = null;

		$format = function($test) use ($type) {
			if ($type == 'html') {
				if ($test == 'group') {
					return '<li><a href="?group=%1$s">%2$s</a><ul>%3$s</ul></li>';
				}
				return '<li><a href="?case=\lithium\tests\cases%2$s\%1$s">%1$s</a></li>';
			}

			if ($type == 'txt') {
				if ($test == 'group') {
					return "-group %1$s\n%2$s\n";
				}
				return "-case %1$s\n";
			}
		};

		if (is_array($data)) {
			foreach ($data as $key => $row) {
				if (is_array($row)) {
					if (is_string($key)) {
						$key = strtolower($key);
						$parent = $parent . '\\' . $key;
						$result = sprintf($format('group'), $parent, $key, static::menu($type, $row, $parent));
					} else {
						$result .= static::menu($type, $row, $parent);
					}
				} else {
					$result .= sprintf($format('case'), $row, $parent);
				}
			}
		}
		if ($parent == null) {
			if ($type == 'html') {
				return sprintf('<ul>%s</ul>', $result);
			}
			if ($type == 'txt') {
				return sprintf("\n%s\n", $result);
			}
		}
		return $result;
	}

	public static function process($results) {
		return array_reduce((array)$results, function($stats, $result) {
			$stats = (array)$stats + array(
				'asserts' => 0,
				'passes' => array(),
				'fails' => array(),
				'exceptions' => array(),
				'errors' => array()
			);
			$result = empty($result[0]) ? array($result) : $result;

			foreach ($result as $response) {
				if (empty($response['result'])) {
					continue;
				}
				$result = $response['result'];

				if (in_array($result, array('fail', 'exception'))) {
					$stats['errors'][] = $response;
				}
				unset($response['file'], $response['result']);

				if (in_array($result, array('pass', 'fail'))) {
					$stats['asserts']++;
				}
				if (in_array($result, array('pass', 'fail', 'exception'))) {
					$stats[Inflector::pluralize($result)][] = $response;
				}
			}
			return $stats;
		});
	}

	protected static function _group($options) {
		if (!empty($options['case'])) {
			return new static::$_classes['group'](array('items' => array(new $options['case'])));
		} elseif (isset($options['group'])) {
			return new static::$_classes['group'](array('items' => (array)$options['group']));
		}
	}

	protected static function _execute($group, $filters) {
		$tests = $group->tests();
		$filterResults = array();

		foreach ($filters as $filter => $options) {
			$options = isset($options['apply']) ? $options['apply'] : array();
			$tests = $filter::apply($tests, $options);
		}
		$results = $tests->run();

		foreach ($filters as $filter => $options) {
			$options = isset($options['analyze']) ? $options['analyze'] : array();
			$filterResults[$filter] = $filter::analyze($results, $options);
		}
		return array($results, $filterResults);
	}
}

?>