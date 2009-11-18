<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test;

use \lithium\core\Libraries;
use \lithium\util\Inflector;

/**
 * Reporter class to handle test report output
 *
 * @param class \lithium\test\Report
 */
class Reporter extends \lithium\core\Object {

	/**
	 * undocumented function
	 *
	 * @param string $report
	 * @param string $options
	 * @return void
	 */
	public static function run($report, $options = array()) {
		$defaults = array('format' => 'text');
		$options += $defaults;
		$reporter = Libraries::locate('test.reporter', Inflector::camelize($options['format']));
		$reporter = new $reporter();
		$classes = Libraries::locate('tests', null, array(
			'filter' => '/cases|integration|functional/'
		));
		$menu = static::menu($classes);
		return $reporter->render(compact('report', 'menu', 'classes'));
	}

	/**
	 * return menu as a string to be used as render
	 *
	 * @params array options
	 *               - format: type of reporter class. eg: html default: text
	 *               - recursive: if true organizes and renders as hierarchy
	 */
	public static function menu($classes, $options = array()) {
		$defaults = array('format' => 'text', 'recursive' => false);
		$options += $defaults;
		if ($options['recursive']) {
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
		$reporter = Libraries::locate('test.reporter', Inflector::camelize($options['format']));
		$reporter = new $reporter();

		if ($options['recursive']) {
			$menu = function ($data, $parent = null) use (&$menu, &$reporter, $result) {
				foreach ($data as $key => $row) {
					if (is_array($row) && is_string($key)) {
						$key = strtolower($key);
						$next = $parent . '\\' . $key;
						$result .= $reporter->format('group', array(
							'namespace' => $next, 'name' => $key, 'menu' => $menu($row, $next)
						));
					} else {
						$next = $parent . '\\' . $key;
						$result .= $reporter->format('case', array(
							'namespace' => $parent, 'name' => $row,
						));
					}
				}
				return $format($result);
			};
			foreach ($classes as $library => $tests) {
				$group = "\\{$library}\\tests";
				$result .= $report->format(null, array('menu' => $report->format('group', array(
					'namespace' => $group, 'name' => $library, 'menu' => $menu($tests, $group)
				))));
			}
			return $result;
		}
		foreach ($classes as $test) {
			$parts = explode('\\', $test);
			$name = array_pop($parts);
			$namespace = join('\\', $parts);
			$result .= $reporter->format('case', compact('namespace', 'name'));
		}
		return $reporter->format(null, array('menu' => $result));
	}
}

?>