<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\core;

use lithium\core\Libraries;
use lithium\aop\Filters;
use lithium\analysis\Inspector;

/**
 * Provides a base class for all static classes in the Lithium framework. Similar to its
 * counterpart, the `Object` class, `StaticObject` defines some utility useful for testing purposes.
 *
 * @see lithium\core\Object
 */
class StaticObject {


	/**
	 * Calls a method on this object with the given parameters. Provides an OO wrapper for
	 * `forward_static_call_array()`, and improves performance by using straight method calls
	 * in most cases.
	 *
	 * @param string $method Name of the method to call.
	 * @param array $params Parameter list to use when calling `$method`.
	 * @return mixed Returns the result of the method call.
	 */
	public static function invokeMethod($method, $params = []) {
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
				return forward_static_call_array([get_called_class(), $method], $params);
		}
	}

	/**
	 * Determines if a given method can be called.
	 *
	 * @param string $method Name of the method.
	 * @param boolean $internal Provide `true` to perform check from inside the
	 *                class/object. When `false` checks also for public visibility;
	 *                defaults to `false`.
	 * @return boolean Returns `true` if the method can be called, `false` otherwise.
	 */
	public static function respondsTo($method, $internal = false) {
		return Inspector::isCallable(get_called_class(), $method, $internal);
	}

	/**
	 * Returns an instance of a class with given `config`. The `name` could be a key from the
	 * `classes` array, a fully namespaced class name, or an object. Typically this method is used
	 * in `_init` to create the dependencies used in the current class.
	 *
	 * @param string|object $name A `classes` key or fully-namespaced class name.
	 * @param array $options The configuration passed to the constructor.
	 * @return object
	 */
	protected static function _instance($name, array $options = []) {
		if (is_string($name) && isset(static::$_classes[$name])) {
			$name = static::$_classes[$name];
		}
		return Libraries::instance(null, $name, $options);
	}

	/* Deprecated / BC */

	/**
	 * Keeps a cached list of each class' inheritance tree.
	 *
	 * @deprecated
	 * @var array
	 */
	protected static $_parents = [];

	/**
	 * Exit immediately. Primarily used for overrides during testing.
	 *
	 * @deprecated
	 * @param integer|string $status integer range 0 to 254, string printed on exit
	 * @return void
	 */
	protected static function _stop($status = 0) {
		$message  = '`' . __METHOD__ . '()` has been deprecated.';
		trigger_error($message, E_USER_DEPRECATED);
		exit($status);
	}

	/**
	 * Gets and caches an array of the parent methods of a class.
	 *
	 * @deprecated
	 * @return array Returns an array of parent classes for the current class.
	 */
	protected static function _parents() {
		$message  = '`' . __METHOD__ . '()` has been deprecated. For property merging ';
		$message .= 'use `\lithium\core\MergeInheritable::_inherit()`';
		trigger_error($message, E_USER_DEPRECATED);

		$class = get_called_class();

		if (!isset(self::$_parents[$class])) {
			static::$_parents[$class] = class_parents($class);
		}
		return static::$_parents[$class];
	}
}

?>