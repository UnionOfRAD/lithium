<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use \lithium\util\collection\Filters;
use \SplStack;

/**
 * Alternative base class in Lithium hierarchy, from which all (and only) static classes inherit.
 *
 * @package Lithium
 */
class StaticObject {

	/**
	 * Stores configuration information for object instances at time of construction.
	 * **Do not override.** Pass any additional variables to `parent::__construct()`.
	 *
	 * @var array
	 */
	protected static $_config = array();

	protected static $_methodFilters = array();

	protected static $_extendMethodFilters = array();

	public static function __init() {}

	/**
	 * Apply a closure to a method of the current static object.
	 *
	 * @param mixed $method The name of the method to apply the closure to. Can either be a single
	 *              method name as a string, or an array of method names.
	 * @param closure $closure The clousure that is used to filter the method.
	 * @return void
	 * @see lithium\core\StaticObject::_filter()
	 * @see lithium\util\collection\Filters
	 */
	public static function applyFilter($method, $closure = null) {
		foreach ((array)$method as $m) {
			if (!isset(static::$_methodFilters[$m])) {
				static::$_methodFilters[$m] = array();
			}
			static::$_methodFilters[$m][] = $closure;
		}
	}

	/**
	 * Applies the configured strategies to a method of the current static object.
	 *
	 * @param  string $method The strategy method to be called.
	 * @param  array  $params Parameters that are used by the strategy $method.
	 * @return mixed          Data that has been modified by the configured strategies.
	 **/
	public static function applyStrategies($method, $params = array()) {
		$strategies = self::strategies($params['name']);

		//switch ($method) {
			//case 'read':
				//$mode = SplStack::IT_MODE_LIFO | SplStack::IT_MODE_KEEP;
				//break;
			//case 'write':
				//$mode = SplStack::IT_MODE_FIFO | SplStack::IT_MODE_KEEP;
				//break;
		//}

		//$strategies->setIteratorMode($mode);
		foreach ($strategies as $strategy) {
			$strategy::$method($params);
		}

		return $params['data'];
	}

	/**
	 * Allows setting & querying of static object strategies.
	 *
	 *
	 * - If $name is set, returns the strategies attached to the current static object.
	 * - If $name and $strategy are set, $strategy is added to the strategy
	 * stack denoted by $name.
	 * - If $name and $strategy are not set, then the full
	 * indexed strategies array is returned (note: the strategies are wraped in
	 * \SplStack).
	 *
	 * @param  string        $name     Name of cache configuration.
	 * @param  string|array  $strategy Fully namespaced cache strategy identifier.
	 * @return mixed                   See above description.
	 */
	public static function strategies($name = '', $strategy = null) {
		if (empty($name)) {
			return static::$_strategies;
		}

		if (!isset(static::$_strategies[$name])) {
			static::$_strategies[$name] = new SplStack();
		}

		if (!empty($strategy)) {
			$strategies = static::$_strategies[$name];

			if (is_array($strategy)) {
				array_walk($strategy, function($value) use (&$strategies) {
					$strategies->push($value);
				});
			}
			else if (is_string($strategy)) {
				$strategies->push($strategy);
			}

			return true;
		}

		return static::$_strategies[$name];
	}
	/**
	 * Calls a method on this object with the given parameters. Provides an OO wrapper
	 * for call_user_func_array, and improves performance by using straight method calls
	 * in most cases.
	 *
	 * @param string $method  Name of the method to call
	 * @param array $params  Parameter list to use when calling $method
	 * @return mixed  Returns the result of the method call
	 */
	public static function invokeMethod($method, $params = array()) {
		switch (count($params)) {
			case 0:
				return static::$method();
			case 1:
				return static::$method($params[0]);
			case 2:
				return static::$method($params[0], $params[1]);
			case 3:
				return static::$method($params[0], $params[1], $params[2]);
			case 4:
				return static::$method($params[0], $params[1], $params[2], $params[3]);
			case 5:
				return static::$method($params[0], $params[1], $params[2], $params[3], $params[4]);
			default:
				return call_user_func_array(array(get_called_class(), $method), $params);
		}
	}

	/**
	 * Executes a set of filters against a method by taking a method's main implementation as a
	 * callback, and iteratively wrapping the filters around it.
	 *
	 * @param string|array $method The name of the method being executed, or an array containing
	 *        the name of the class that defined the method, and the method name.
	 * @param array $params An associative array containing all the parameters passed into
	 *        the method.
	 * @param Closure $callback The method's implementation, wrapped in a closure.
	 * @param array $filters Additional filters to apply to the method for this call only
	 * @return mixed
	 */
	protected static function _filter($method, $params, $callback, $filters = array()) {
		list($class, $method) = explode('::', $method);

		if (empty(static::$_methodFilters[$method]) && empty($filters)) {
			return $callback->__invoke($class, $params, null);
		}

		$f = isset(static::$_methodFilters[$method]) ? static::$_methodFilters[$method] : array();
		$items = array_merge($f, $filters, array($callback));
		$chain = new Filters(compact('items', 'class', 'method'));

		$start = $chain->rewind();
		return $start($class, $params, $chain);
	}

	/**
	 * Exit immediately.  Primarily used for overrides during testing.
	 *
	 * @return void
	 */
	protected static function _stop() {
		exit();
	}
}

?>