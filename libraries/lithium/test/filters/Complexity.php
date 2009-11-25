<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\filters;

use \lithium\util\reflection\Parser;
use \lithium\util\reflection\Inspector;

/**
 * Calculates the cyclomatic complexity of class methods, and shows worst-offenders and statistics.
 */
class Complexity extends \lithium\core\StaticObject {

	/**
	 * Collects complexity analysis results for classes/methods.
	 */
	protected static $_results = array();

	/**
	 * The list of tokens which represent the starting point of a code branch.
	 */
	protected static $_include = array(
		'T_CASE', 'T_DEFAULT', 'T_CATCH', 'T_IF', 'T_FOR',
		'T_FOREACH', 'T_WHILE', 'T_DO', 'T_ELSEIF'
	);

	/**
	 * Takes an instance of `test\Group` containing one or more test cases introspects the test
	 * subject classes to extract cyclomatic complexity data.
	 *
	 * @param object $object Instance of `Group` containing instances of `lithium\test\Unit`.
	 * @param array $options Not implemented
	 * @return object Returns the value of the `$object` parameter.
	 */
	public static function apply($object, $options = array()) {
		$defaults = array();
		$options += $defaults;

		foreach ($object->invoke('subject') as $class) {
			static::$_results[$class] = array();

			foreach (Inspector::methods($class, 'ranges') as $method => $lines) {
				$lines = Inspector::lines($class, $lines);
				$branches = Parser::tokenize(join("\n", (array)$lines), array(
					'include' => static::$_include
				));
				static::$_results[$class][$method] = count($branches) + 1;
			}
		}
		return $object;
	}

	public static function analyze($results, $classes = array()) {
		$metrics = array('max' => array(), 'class' => array());

		foreach (static::$_results as $class => $methods) {
			if (!$methods) {
				continue;
			}
			$metrics['class'][$class] = array_sum($methods) / count($methods);

			foreach ($methods as $method => $count) {
				$metrics['max']["{$class}::{$method}()"] = $count;
			}
		}
		arsort($metrics['max']);
		arsort($metrics['class']);
		return $metrics;
	}

	public static function output($format, $data) {
		echo '<h3>Cyclomatic Complexity</h3>';
		echo '<table class="metrics"><tbody>';

		foreach (array_slice($data['max'], 0, 10) as $method => $count) {
			if ($count <= 7) {
				continue;
			}
			echo '<tr>';
			echo '<td class="metric-name">Worst Offender</th>';
			echo '<td class="metric">' . $method . ' - ' . $count . '</td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<th colspan="2">Class Averages</th>';
		echo '</tr>';

		foreach (array_slice($data['class'], 0, 10) as $class => $count) {
			echo '<tr>';
			echo '<td class="metric-name">' . $class . '</th>';
			echo '<td class="metric">' . round($count, 2) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
}

?>