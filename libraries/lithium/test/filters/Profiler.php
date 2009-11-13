<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\filters;

class Profiler extends \lithium\core\StaticObject {

	/**
	 * Collects profiling results from test-wrapping filters.
	 */
	protected static $_results = array();

	/**
	 * Maps class names to test class names
	 */
	protected static $_classMap = array();

	/**
	 * Contains the list of profiler checks to run against each test.  Values can be string
	 * function names, arrays containing function names as the first key and function parameters
	 * as subsequent keys, or closures.
	 *
	 * @var array
	 * @see lithium\test\Profiler::check()
	 */
	protected static $_metrics = array(
		'Time' => array(
			'function' => array('microtime', true),
			'format' => 'seconds'
		),
		'Current Memory' => array(
			'function' => 'memory_get_usage',
			'format' => 'bytes'
		),
		'Peak Memory' => array(
			'function' => 'memory_get_peak_usage',
			'format' => 'bytes'
		),
		'Current Memory (Xdebug)' => array(
			'function' => 'xdebug_memory_usage',
			'format' => 'bytes'
		),
		'Peak Memory (Xdebug)' => array(
			'function' => 'xdebug_peak_memory_usage',
			'format' => 'bytes'
		)
	);

	protected static $_formatters = array();

	/**
	 * Verifies that the corresponding function exists for each built-in profiler check.
	 * Initializes display formatters.
	 *
	 * @return void
	 */
	public static function __init() {
		foreach (static::$_metrics as $name => $check) {
			$function = current((array)$check['function']);

			if (is_string($check['function']) && !function_exists($check['function'])) {
				unset(static::$_metrics[$name]);
			}
		}

		static::$_formatters = array(
			'seconds' => function($value) { return number_format($value, 4) . 's'; },
			'bytes' => function($value) { return number_format($value / 1024, 3) . 'k'; }
		);
	}

	/**
	 * Add, remove, or modify a profiler check.
	 *
	 * @param mixed $name 
	 * @param string $value 
	 * @see lithium\test\Profiler::$_metrics
	 * @return mixed
	 */
	public function check($name, $value = null) {
		if (is_null($value) && !is_array($name)) {
			return isset(static::$_metrics[$name]) ? static::$_metrics[$name] : null;
		}

		if ($value === false) {
			unset(static::$_metrics[$name]);
			return;
		}

		if (!empty($value)) {
			static::$_metrics[$name] = $value;
		}

		if (is_array($name)) {
			static::$_metrics = $name + static::$_metrics;
		}
	}

	public static function apply($object, $options = array()) {
		$defaults = array('method' => 'run', 'checks' => static::$_metrics);
		$options += $defaults;
		$m = $options['method'];

		$object->invoke('applyFilter', array($m, function($self, $params, $chain) use ($options) {
			$start = $results = array();

			$runCheck = function($check) {
				switch (true) {
					case (is_object($check) || is_string($check)):
						return $check();
					break;
					case (is_array($check)):
						$function = array_shift($check);
						$result = !$check ? $check() : call_user_func_array($function, $check);
					break;
				}
				return $result;
			};

			foreach ($options['checks'] as $name => $check) {
				$start[$name] = $runCheck($check['function']);
			}
			$methodResult = $chain->next($self, $params, $chain);

			foreach ($options['checks'] as $name => $check) {
				$results[$name] = $runCheck($check['function']) - $start[$name];
			}
			Profiler::collect($self->subject(), $params['method'], $results, $options + array(
				'test' => get_class($self)
			));
			return $methodResult;
		}));
		return $object;
	}

	public static function collect($class, $method, $results, $options = array()) {
		$defaults = array('test' => null);
		$options += $defaults;

		static::$_classMap[$options['test']] = $class;
		static::$_results[$class][$method] = $results;
	}

	public static function analyze($results, $classes = array()) {
		$metrics = array();

		foreach ($results as $testCase) {
			foreach ($testCase as $assertion) {
				if ($assertion['result'] != 'pass' && $assertion['result'] != 'fail') {
					continue;
				}
				$class = static::$_classMap[$assertion['class']];

				if (!isset($metrics[$class])) {
					$metrics[$class] = array('assertions' => 0);
				}
				$metrics[$class]['assertions']++;
			}
		}

		foreach (static::$_results as $class => $methods) {
			foreach ($methods as $methodName => $timers) {
				foreach ($timers as $title => $value) {
					if (!isset($metrics[$class][$title])) {
						$metrics[$class][$title] = 0;
					}
					$metrics[$class][$title] += $value;
				}
			}
		}
		return $metrics;
	}

	public static function output($format, $data) {
		$totals = array();

		foreach ($data as $class => $metrics) {
			foreach ($metrics as $title => $value) {
				$totals[$title] = isset($totals[$title]) ? $totals[$title] : 0;
				$totals[$title]+= $value;
			}
		}
		echo '<h3>Benchmarks</h3>';
		echo '<table class="metrics"><tbody>';

		foreach ($totals as $title => $value) {
			if (!isset(static::$_metrics[$title])) {
				continue;
			}
			$formatter = static::$_formatters[static::$_metrics[$title]['format']];
			echo '<tr>';
 			echo '<td class="metric-name">' . $title . '</th>';
			echo '<td class="metric">' . $formatter($value) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
}

?>