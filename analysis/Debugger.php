<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\analysis;

use ReflectionClass;
use lithium\util\Text;
use lithium\analysis\Inspector;

/**
 * The `Debugger` class provides basic facilities for generating and rendering meta-data about the
 * state of an application in its current context.
 */
class Debugger {

	/**
	 * Used for temporary closure caching.
	 *
	 * @see lithium\analysis\Debugger::_closureDef()
	 * @var array
	 */
	protected static $_closureCache = [];

	/**
	 * Outputs a stack trace based on the supplied options.
	 *
	 * @param array $options Format for outputting stack trace. Available options are:
	 *        - `'args'`: A boolean indicating if arguments should be included.
	 *        - `'depth'`: The maximum depth of the trace.
	 *        - `'format'`: Either `null`, `'points'` or `'array'`.
	 *        - `'includeScope'`: A boolean indicating if items within scope
	 *           should be included.
	 *        - `'scope'`: Scope for items to include.
	 *        - `'start'`: The depth to start with.
	 *        - `'trace'`: A trace to use instead of generating one.
	 * @return string|array|null Stack trace formatted according to `'format'` option.
	 */
	public static function trace(array $options = []) {
		$defaults = [
			'depth' => 999,
			'format' => null,
			'args' => false,
			'start' => 0,
			'scope' => [],
			'trace' => [],
			'includeScope' => true,
			'closures' => true
		];
		$options += $defaults;

		$backtrace = $options['trace'] ?: debug_backtrace();
		$scope = $options['scope'];
		$count = count($backtrace);
		$back = [];
		$traceDefault = [
			'line' => '??', 'file' => '[internal]', 'class' => null, 'function' => '[main]'
		];

		for ($i = $options['start']; $i < $count && $i < $options['depth']; $i++) {
			$trace = array_merge(['file' => '[internal]', 'line' => '??'], $backtrace[$i]);
			$function = '[main]';

			if (isset($backtrace[$i + 1])) {
				$next = $backtrace[$i + 1] + $traceDefault;
				$function = $next['function'];

				if (!empty($next['class'])) {
					$function = $next['class'] . '::' . $function . '(';
					if ($options['args'] && isset($next['args'])) {
						$args = array_map(['static', 'export'], $next['args']);
						$function .= join(', ', $args);
					}
					$function .= ')';
				}
			}

			if ($options['closures'] && strpos($function, '{closure}') !== false) {
				$function = static::_closureDef($backtrace[$i], $function);
			}
			if (in_array($function, ['call_user_func_array', 'trigger_error'])) {
				continue;
			}
			$trace['functionRef'] = $function;

			if ($options['format'] === 'points' && $trace['file'] !== '[internal]') {
				$back[] = ['file' => $trace['file'], 'line' => $trace['line']];
			} elseif (is_string($options['format']) && $options['format'] !== 'array') {
				$back[] = Text::insert($options['format'], array_map(
					function($data) { return is_object($data) ? get_class($data) : $data; },
					$trace
				));
			} elseif (empty($options['format'])) {
				$back[] = $function . ' - ' . $trace['file'] . ', line ' . $trace['line'];
			} else {
				$back[] = $trace;
			}

			if (!empty($scope) && array_intersect_assoc($scope, $trace) == $scope) {
				if (!$options['includeScope']) {
					$back = array_slice($back, 0, count($back) - 1);
				}
				break;
			}
		}

		if ($options['format'] === 'array' || $options['format'] === 'points') {
			return $back;
		}
		return join("\n", $back);
	}

	/**
	 * Returns a parseable string representation of a variable.
	 *
	 * @param mixed $var The variable to export.
	 * @return string The exported contents.
	 */
	public static function export($var) {
		$export = var_export($var, true);

		if (is_array($var)) {
			$replace = [" (", " )", "  ", " )", "=> \n\t"];
			$with = ["(", ")", "\t", "\t)", "=> "];
			$export = str_replace($replace, $with, $export);
		}
		return $export;
	}

	/**
	 * Locates original location of closures.
	 *
	 * @param mixed $reference File or class name to inspect.
	 * @param integer $callLine Line number of class reference.
	 * @return mixed Returns the line number where the method called is defined.
	 */
	protected static function _definition($reference, $callLine) {
		if (file_exists($reference)) {
			foreach (array_reverse(token_get_all(file_get_contents($reference))) as $token) {
				if (!is_array($token) || $token[2] > $callLine) {
					continue;
				}
				if ($token[0] === T_FUNCTION) {
					return $token[2];
				}
			}
			return;
		}
		list($class,) = explode('::', $reference);

		if (!$class || !class_exists($class)) {
			return;
		}

		$classRef = new ReflectionClass($class);
		$methodInfo = Inspector::info($reference);
		$methodDef = join("\n", Inspector::lines($classRef->getFileName(), range(
			$methodInfo['start'] + 1, $methodInfo['end'] - 1
		)));

		foreach (array_reverse(token_get_all("<?php {$methodDef} ?>")) as $token) {
			if (!is_array($token) || $token[2] > $callLine) {
				continue;
			}
			if ($token[0] === T_FUNCTION) {
				return $token[2] + $methodInfo['start'];
			}
		}
	}

	/**
	 * Helper method for caching closure function references to help the process of building the
	 * stack trace.
	 *
	 * @param  array $frame Backtrace information.
	 * @param  callable|string $function The method related to $frame information.
	 * @return string Returns either the cached or the fetched closure function reference while
	 *                writing its reference to the cache array `$_closureCache`.
	 */
	protected static function _closureDef($frame, $function) {
		$reference = '::';
		$frame += ['file' => '??', 'line' => '??'];
		$cacheKey = "{$frame['file']}@{$frame['line']}";

		if (isset(static::$_closureCache[$cacheKey])) {
			return static::$_closureCache[$cacheKey];
		}

		if ($class = Inspector::classes(['file' => $frame['file']])) {
			foreach (Inspector::methods(key($class), 'extents') as $method => $extents) {
				$line = $frame['line'];

				if (!($extents[0] <= $line && $line <= $extents[1])) {
					continue;
				}
				$class = key($class);
				$reference = "{$class}::{$method}";
				$function = "{$reference}()::{closure}";
				break;
			}
		} else {
			$reference = $frame['file'];
			$function = "{$reference}::{closure}";
		}
		$line = static::_definition($reference, $frame['line']) ?: '?';
		$function .= " @ {$line}";
		return static::$_closureCache[$cacheKey] = $function;
	}
}

?>