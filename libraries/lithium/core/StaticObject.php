<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use \lithium\util\collection\Filters;

/**
 * Provides a base class for all static classes in the Lithium framework. Similar to its
 * counterpart, the `Object` class, `StaticObject` defines some utility methods for working with
 * the filters system, and methods useful for testing purposes.
 *
 * @see lithium\core\Object
 */
class StaticObject {

	/**
	 * Stores the closures that represent the method filters. They are indexed by called class.
	 *
	 * @var array Method filters, indexed by `get_called_class()`.
	 */
	protected static $_methodFilters = array();

	/**
	 * Keeps a cached list of each class' inheritance tree.
	 *
	 * @var array
	 */
	protected static $_parents = array();

	/**
	 * Apply a closure to a method of the current static object.
	 *
	 * @see lithium\core\StaticObject::_filter()
	 * @see lithium\util\collection\Filters
	 * @param mixed $method The name of the method to apply the closure to. Can either be a single
	 *        method name as a string, or an array of method names.
	 * @param closure $closure The closure that is used to filter the method.
	 * @return void
	 */
	public static function applyFilter($method, $closure = null) {
		$class = get_called_class();
		foreach ((array) $method as $m) {
			if (!isset(static::$_methodFilters[$class][$m])) {
				static::$_methodFilters[$class][$m] = array();
			}
			static::$_methodFilters[$class][$m][] = $closure;
		}
	}

	/**
	 * Calls a method on this object with the given parameters. Provides an OO wrapper for
	 * `call_user_func_array()`, and improves performance by using straight method calls in most
	 * cases.
	 *
	 * @param string $method Name of the method to call.
	 * @param array $params Parameter list to use when calling `$method`.
	 * @return mixed Returns the result of the method call.
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
	 * @see lithium\util\collection\Filters
	 * @param string|array $method The name of the method being executed, or an array containing
	 *        the name of the class that defined the method, and the method name.
	 * @param array $params An associative array containing all the parameters passed into
	 *        the method.
	 * @param Closure $callback The method's implementation, wrapped in a closure.
	 * @param array $filters Additional filters to apply to the method for this call only.
	 * @return mixed
	 */
	protected static function _filter($method, $params, $callback, $filters = array()) {
		$class = get_called_class();

		if (empty(static::$_methodFilters[$class][$method]) && empty($filters)) {
			return $callback($class, $params, null);
		}

		if (!isset(static::$_methodFilters[$class][$method])) {
			static::$_methodFilters += array($class => array());
			static::$_methodFilters[$class][$method] = array();
		}
		$items = array_merge(static::$_methodFilters[$class][$method], $filters, array($callback));
		return Filters::run($class, $params, compact('items', 'class', 'method'));
	}

	/**
	 * Gets and caches an array of the parent methods of a class.
	 *
	 * @return array Returns an array of parent classes for the current class.
	 */
	protected static function _parents() {
		$class = get_called_class();

		if (!isset(self::$_parents[$class])) {
			self::$_parents[$class] = class_parents($class);
		}
		return self::$_parents[$class];
	}

	/**
	 * Exit immediately. Primarily used for overrides during testing.
	 *
	 * @return void
	 */
	protected static function _stop() {
		exit();
	}
}

?>