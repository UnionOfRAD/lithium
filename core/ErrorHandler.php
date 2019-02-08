<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\core;

use Exception;
use ErrorException;
use lithium\aop\Filters;

/**
 * The `ErrorHandler` class allows PHP errors and exceptions to be handled in a uniform way. Using
 * the `ErrorHandler`'s configuration, it is possible to have very broad but very tight control
 * over error handling in your application.
 *
 * ``` embed:lithium\tests\cases\core\ErrorHandlerTest::testExceptionCatching(2-7) ```
 *
 * Using a series of cascading rules and handlers, it is possible to capture and handle very
 * specific errors and exceptions.
 */
class ErrorHandler extends \lithium\core\StaticObject {

	/**
	 * Configuration parameters.
	 *
	 * @var array Config params
	 */
	protected static $_config = [];

	/**
	 * Types of checks available for sorting & parsing errors/exceptions.
	 * Default checks are for `code`, `stack` and `message`.
	 *
	 * @var array Array of checks represented as closures, indexed by name.
	 */
	protected static $_checks = [];

	/**
	 * Currently registered exception handler.
	 *
	 * @var \Closure Closure representing exception handler.
	 */
	protected static $_exceptionHandler = null;

	/**
	 * State of error/exception handling.
	 *
	 * @var boolean True if custom error/exception handlers have been registered, false
	 *      otherwise.
	 */
	protected static $_isRunning = false;

	protected static $_runOptions = [];

	/**
	 * Configure the `ErrorHandler`.
	 *
	 * @param array $config Configuration directives.
	 * @return Current configuration set.
	 */
	public static function config($config = []) {
		return (static::$_config = array_merge($config, static::$_config));
	}

	/**
	 * Register error and exception handlers.
	 *
	 * This method (`ErrorHandler::run()`) needs to be called as early as possible in the bootstrap
	 * cycle; immediately after `require`-ing `bootstrap/libraries.php` is your best bet.
	 *
	 * @param array $config The configuration with which to start the error handler. Available
	 *              options include:
	 *              - `'trapErrors'` _boolean_: Defaults to `false`. If set to `true`, PHP errors
	 *                will be caught by `ErrorHandler` and handled in-place. Execution will resume
	 *                in the same context in which the error occurred.
	 *              - `'convertErrors'` _boolean_: Defaults to `true`, and specifies that all PHP
	 *                errors should be converted to `ErrorException`s and thrown from the point
	 *                where the error occurred. The exception will be caught at the first point in
	 *                the stack trace inside a matching `try`/`catch` block, or that has a matching
	 *                error handler applied using the `apply()` method.
	 */
	public static function run(array $config = []) {
		$defaults = ['trapErrors' => false, 'convertErrors' => true];

		if (static::$_isRunning) {
			return;
		}
		static::$_isRunning = true;
		static::$_runOptions = $config + $defaults;

		$trap = function($code, $message, $file, $line = 0, $context = null) {
			$trace = debug_backtrace();
			$trace = array_slice($trace, 1, count($trace));
			static::handle(compact('type', 'code', 'message', 'file', 'line', 'trace', 'context'));
		};

		$convert = function($code, $message, $file, $line = 0, $context = null) {
			throw new ErrorException($message, 500, $code, $file, $line);
		};

		if (static::$_runOptions['trapErrors']) {
			set_error_handler($trap);
		} elseif (static::$_runOptions['convertErrors']) {
			set_error_handler($convert);
		}
		set_exception_handler(static::$_exceptionHandler);
	}

	/**
	 * Returns the state of the `ErrorHandler`, indicating whether or not custom error/exception
	 * handers have been regsitered.
	 */
	public static function isRunning() {
		return static::$_isRunning;
	}

	/**
	 * Unooks `ErrorHandler`'s exception and error handlers, and restores PHP's defaults. May have
	 * unexpected results if it is not matched with a prior call to `run()`, or if other error
	 * handlers are set after a call to `run()`.
	 */
	public static function stop() {
		restore_error_handler();
		restore_exception_handler();
		static::$_isRunning = false;
	}

	/**
	 * Setup basic error handling checks/types, as well as register the error and exception
	 * handlers and wipes out all configuration and resets the error handler to its initial state
	 * when loaded. Mainly used for testing.
	 */
	public static function reset() {
		static::$_config = [];
		static::$_checks = [];
		static::$_exceptionHandler = null;
		static::$_checks = [
			'type'  => function($config, $info) {
				return (boolean) array_filter((array) $config['type'], function($type) use ($info) {
					return $type === $info['type'] || is_subclass_of($info['type'], $type);
				});
			},
			'code' => function($config, $info) {
				return ($config['code'] & $info['code']);
			},
			'stack' => function($config, $info) {
				return (boolean) array_intersect((array) $config['stack'], $info['stack']);
			},
			'message' => function($config, $info) {
				return preg_match($config['message'], $info['message']);
			}
		];
		static::$_exceptionHandler = function($exception, $return = false) {
			if (ob_get_length()) {
				ob_end_clean();
			}
			$info = compact('exception') + [
				'type' => get_class($exception),
				'stack' => static::trace($exception->getTrace())
			];
			foreach (['message', 'file', 'line', 'trace'] as $key) {
				$method = 'get' . ucfirst($key);
				$info[$key] = $exception->{$method}();
			}
			return $return ? $info : static::handle($info);
		};
	}

	/**
	 * Receives the handled errors and exceptions that have been caught, and processes them
	 * in a normalized manner.
	 *
	 * @param object|array $info
	 * @param array $scope
	 * @return boolean True if successfully handled, false otherwise.
	 */
	public static function handle($info, $scope = []) {
		$checks = static::$_checks;
		$rules = $scope ?: static::$_config;
		$handler = static::$_exceptionHandler;
		$info = is_object($info) ? $handler($info, true) : $info;

		$defaults = [
			'type' => null, 'code' => 0, 'message' => null, 'file' => null, 'line' => 0,
			'trace' => [], 'context' => null, 'exception' => null
		];
		$info = (array) $info + $defaults;

		$info['stack'] = static::trace($info['trace']);
		$info['origin'] = static::_origin($info['trace']);

		foreach ($rules as $config) {
			foreach (array_keys($config) as $key) {
				if ($key === 'conditions' || $key === 'scope' || $key === 'handler') {
					continue;
				}
				if (!isset($info[$key]) || !isset($checks[$key])) {
					continue 2;
				}
				if (($check = $checks[$key]) && !$check($config, $info)) {
					continue 2;
				}
			}
			if (!isset($config['handler'])) {
				return false;
			}
			if ((isset($config['conditions']) && $call = $config['conditions']) && !$call($info)) {
				return false;
			}
			if ((isset($config['scope'])) && static::handle($info, $config['scope']) !== false) {
				return true;
			}
			$handler = $config['handler'];
			return $handler($info) !== false;
		}
		return false;
	}

	/**
	 * Determine frame from the stack trace where the error/exception was first generated.
	 *
	 * @param array $stack Stack trace from error/exception that was produced.
	 * @return string Class where error/exception was generated.
	 */
	protected static function _origin(array $stack) {
		foreach ($stack as $frame) {
			if (isset($frame['class'])) {
				return trim($frame['class'], '\\');
			}
		}
	}

	public static function apply($object, array $conditions, $handler) {
		$conditions = $conditions ?: ['type' => 'Exception'];
		list($class, $method) = is_string($object) ? explode('::', $object) : $object;

		Filters::apply($class, $method, function($params, $next) use ($conditions, $handler) {
			$wrap = static::$_exceptionHandler;

			try {
				return $next($params);
			} catch (Exception $e) {
				if (!static::matches($e, $conditions)) {
					throw $e;
				}
				return $handler($wrap($e, true), $params);
			}
		});
	}

	public static function matches($info, $conditions) {
		$checks = static::$_checks;
		$handler = static::$_exceptionHandler;
		$info = is_object($info) ? $handler($info, true) : $info;

		foreach (array_keys($conditions) as $key) {
			if ($key === 'conditions' || $key === 'scope' || $key === 'handler') {
				continue;
			}
			if (!isset($info[$key]) || !isset($checks[$key])) {
				return false;
			}
			if (($check = $checks[$key]) && !$check($conditions, $info)) {
				return false;
			}
		}
		if ((isset($config['conditions']) && $call = $config['conditions']) && !$call($info)) {
			return false;
		}
		return true;
	}

	/**
	 * Trim down a typical stack trace to class & method calls.
	 *
	 * @param array $stack A `debug_backtrace()`-compatible stack trace output.
	 * @return array Returns a flat stack array containing class and method references.
	 */
	public static function trace(array $stack) {
		$result = [];

		foreach ($stack as $frame) {
			if (isset($frame['function'])) {
				if (isset($frame['class'])) {
					$result[] = trim($frame['class'], '\\') . '::' . $frame['function'];
				} else {
					$result[] = $frame['function'];
				}
			}
		}
		return $result;
	}
}

ErrorHandler::reset();

?>