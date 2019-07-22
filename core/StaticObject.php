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
 * @deprecated
 * @see lithium\core\Object
 */
class StaticObject {

	/**
	 * Determines if a given method can be called.
	 *
	 * @deprecated
	 * @param string $method Name of the method.
	 * @param boolean $internal Provide `true` to perform check from inside the
	 *                class/object. When `false` checks also for public visibility;
	 *                defaults to `false`.
	 * @return boolean Returns `true` if the method can be called, `false` otherwise.
	 */
	public static function respondsTo($method, $internal = false) {
		$message  = '`' . __METHOD__ . '()` has been deprecated. ';
		$message .= "Use `is_callable('<class>::<method>')` instead.";
		trigger_error($message, E_USER_DEPRECATED);

		return Inspector::isCallable(get_called_class(), $method, $internal);
	}

	/**
	 * Calls a method on this object with the given parameters. Provides an OO wrapper for
	 * `forward_static_call_array()`, and improves performance by using straight method calls
	 * in most cases.
	 *
	 * @deprecated
	 * @param string $method Name of the method to call.
	 * @param array $params Parameter list to use when calling `$method`.
	 * @return mixed Returns the result of the method call.
	 */
	public static function invokeMethod($method, $params = []) {
		$message  = '`' . __METHOD__ . '()` has been deprecated.';
		trigger_error($message, E_USER_DEPRECATED);

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
	 * Returns an instance of a class with given `config`. The `name` could be a key from the
	 * `classes` array, a fully namespaced class name, or an object. Typically this method is used
	 * in `_init` to create the dependencies used in the current class.
	 *
	 * @deprecated
	 * @param string|object $name A `classes` key or fully-namespaced class name.
	 * @param array $options The configuration passed to the constructor.
	 * @return object
	 */
	protected static function _instance($name, array $options = []) {
		$message  = '`' . __METHOD__ . '()` has been deprecated. ';
		$message .= 'Please use Libraries::instance(), with the 4th parameter instead.';
		trigger_error($message, E_USER_DEPRECATED);
		return Libraries::instance(null, $name, $options, static::$_classes);
	}
}

?>