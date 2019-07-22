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
 * Base class in Lithium's hierarchy, from which all concrete classes inherit. This class defines
 * several conventions for how classes in Lithium should be structured:
 *
 * - **Universal constructor**: Any class which defines a `__construct()` method should take
 *   exactly one parameter (`$config`), and that parameter should always be an array. Any settings
 *   passed to the constructor will be stored in the `$_config` property of the object.
 * - **Initialization / automatic configuration**: After the constructor, the `_init()` method is
 *   called. This method can be used to initialize the object, keeping complex logic and
 *   high-overhead or difficult to test operations out of the constructor. This method is called
 *   automatically by `Object::__construct()`, but may be disabled by passing `'init' => false` to
 *   the constructor. The initializer is also used for automatically assigning object properties.
 *   See the documentation on the `_init()` method for more details.
 *
 * @link http://php.net/manual/en/language.oop5.magic.php#object.set-state
 * @see lithium\core\StaticObject
 */
class Object {

	/**
	 * Stores configuration information for object instances at time of construction.
	 * **Do not override.** Pass any additional variables to `parent::__construct()`.
	 *
	 * @var array
	 */
	protected $_config = [];

	/**
	 * Holds an array of values that should be processed on initialization. Each value should have
	 * a matching protected property (prefixed with `_`) defined in the class. If the property is
	 * an array, the property name should be the key and the value should be `'merge'`. See the
	 * `_init()` method for more details.
	 *
	 * @see lithium\core\Object::_init()
	 * @var array
	 */
	protected $_autoConfig = [];

	/**
	 * Constructor. Initializes class configuration (`$_config`), and assigns object properties
	 * using the `_init()` method, unless otherwise specified by configuration. See below for
	 * details.
	 *
	 * @see lithium\core\Object::$_config
	 * @see lithium\core\Object::_init()
	 * @param array $config The configuration options which will be assigned to the `$_config`
	 *        property. This method accepts one configuration option:
	 *        - `'init'` _boolean_: Controls constructor behavior for calling the `_init()`
	 *          method. If `false`, the method is not called, otherwise it is. Defaults to `true`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['init' => true];
		$this->_config = $config + $defaults;

		if ($this->_config['init']) {
			$this->_init();
		}
	}

	/**
	 * Initializer function called by the constructor unless the constructor `'init'` flag is set
	 * to `false`. May be used for testing purposes, where objects need to be manipulated in an
	 * un-initialized state, or for high-overhead operations that require more control than the
	 * constructor provides. Additionally, this method iterates over the `$_autoConfig` property
	 * to automatically assign configuration settings to their corresponding properties.
	 *
	 * For example, given the following:
	 * ```
	 * class Bar extends \lithium\core\Object {
	 * 	protected $_autoConfig = ['foo'];
	 * 	protected $_foo;
	 * }
	 *
	 * $instance = new Bar(['foo' => 'value']);
	 * ```
	 *
	 * The `$_foo` property of `$instance` would automatically be set to `'value'`. If `$_foo` was
	 * an array, `$_autoConfig` could be set to `array('foo' => 'merge')`, and the constructor value
	 * of `'foo'` would be merged with the default value of `$_foo` and assigned to it.
	 *
	 * @see lithium\core\Object::$_autoConfig
	 * @return void
	 */
	protected function _init() {
		foreach ($this->_autoConfig as $key => $flag) {
			if (!isset($this->_config[$key]) && !isset($this->_config[$flag])) {
				continue;
			}

			if ($flag === 'merge') {
				$this->{"_{$key}"} = $this->_config[$key] + $this->{"_{$key}"};
			} else {
				$this->{"_$flag"} = $this->_config[$flag];
			}
		}
	}

	/* Deprecated / BC */

	/**
	 * Calls a method on this object with the given parameters. Provides an OO wrapper
	 * for call_user_func_array, and improves performance by using straight method calls
	 * in most cases.
	 *
	 * @deprecated
	 * @param string $method  Name of the method to call
	 * @param array $params  Parameter list to use when calling $method
	 * @return mixed  Returns the result of the method call
	 */
	public function invokeMethod($method, $params = []) {
		$message  = '`' . __METHOD__ . '()` has been deprecated.';
		trigger_error($message, E_USER_DEPRECATED);

		switch (count($params)) {
			case 0:
				return $this->{$method}();
			case 1:
				return $this->{$method}($params[0]);
			case 2:
				return $this->{$method}($params[0], $params[1]);
			case 3:
				return $this->{$method}($params[0], $params[1], $params[2]);
			case 4:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3]);
			case 5:
				return $this->{$method}($params[0], $params[1], $params[2], $params[3], $params[4]);
			default:
				return call_user_func_array([&$this, $method], $params);
		}
	}

	/**
	 * Returns an instance of a class with given `config`. The `name` could be a key from the
	 * `classes` array, a fully-namespaced class name, or an object. Typically this method is used
	 * in `_init` to create the dependencies used in the current class.
	 *
	 * @deprecated
	 * @param string|object $name A `classes` key or fully-namespaced class name.
	 * @param array $options The configuration passed to the constructor.
	 * @return object
	 */
	protected function _instance($name, array $options = []) {
		$message  = '`' . __METHOD__ . '()` has been deprecated. ';
		$message .= 'Please use Libraries::instance(), with the 4th parameter instead.';
		trigger_error($message, E_USER_DEPRECATED);
		return Libraries::instance(null, $name, $options, $this->_classes);
	}
}

?>