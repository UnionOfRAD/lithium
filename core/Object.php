<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use lithium\core\Libraries;
use lithium\util\collection\Filters;
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
 * - **Filters**: The `Object` class implements two methods which allow an object to easily
 *   implement filterable methods. The `_filter()` method allows methods to be implemented as
 *   filterable, and the `applyFilter()` method allows filters to be wrapped around them.
 * - **Testing / misc.**: The `__set_state()` method provides a default implementation of the PHP
 *   magic method (works with `var_export()`) which can instantiate an object with a static method
 *   call. Finally, the `_stop()` method may be used instead of `exit()`, as it can be overridden
 *   for testing purposes.
 *
 * @link http://php.net/manual/en/language.oop5.magic.php#object.set-state
 * @see lithium\core\StaticObject
 */
class Object {
	use Filterable;

	/**
	 * Stores configuration information for object instances at time of construction.
	 * **Do not override.** Pass any additional variables to `parent::__construct()`.
	 *
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Holds an array of values that should be processed on initialization. Each value should have
	 * a matching protected property (prefixed with `_`) defined in the class. If the property is
	 * an array, the property name should be the key and the value should be `'merge'`. See the
	 * `_init()` method for more details.
	 *
	 * @see lithium\core\Object::_init()
	 * @var array
	 */
	protected $_autoConfig = array();

	/**
	 * Parents of the current class.
	 *
	 * @see lithium\core\Object::_parents()
	 * @var array
	 */
	protected static $_parents = array();

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
	public function __construct(array $config = array()) {
		$defaults = array('init' => true);
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
	 * 	protected $_autoConfig = array('foo');
	 * 	protected $_foo;
	 * }
	 *
	 * $instance = new Bar(array('foo' => 'value'));
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

	/**
	 * Calls a method on this object with the given parameters. Provides an OO wrapper
	 * for call_user_func_array, and improves performance by using straight method calls
	 * in most cases.
	 *
	 * @param string $method  Name of the method to call
	 * @param array $params  Parameter list to use when calling $method
	 * @return mixed  Returns the result of the method call
	 */
	public function invokeMethod($method, $params = array()) {
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
				return call_user_func_array(array(&$this, $method), $params);
		}
	}

	/**
	 * PHP magic method used in conjunction with `var_export()` to allow objects to be
	 * re-instantiated with their pre-existing properties and values intact. This method can be
	 * called statically on any class that extends `Object` to return an instance of it.
	 *
	 * @param array $data An array of properties and values with which to re-instantiate the object.
	 *        These properties can be both public and protected.
	 * @return object Returns an instance of the requested object with the given properties set.
	 */
	public static function __set_state($data) {
		$class = get_called_class();
		$object = new $class();

		foreach ($data as $property => $value) {
			$object->{$property} = $value;
		}
		return $object;
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
	public function respondsTo($method, $internal = false) {
		return Inspector::isCallable($this, $method, $internal);
	}

	/**
	 * Returns an instance of a class with given `config`. The `name` could be a key from the
	 * `classes` array, a fully-namespaced class name, or an object. Typically this method is used
	 * in `_init` to create the dependencies used in the current class.
	 *
	 * @param string|object $name A `classes` key or fully-namespaced class name.
	 * @param array $options The configuration passed to the constructor.
	 * @return object
	 */
	protected function _instance($name, array $options = array()) {
		if (is_string($name) && isset($this->_classes[$name])) {
			$name = $this->_classes[$name];
		}
		return Libraries::instance(null, $name, $options);
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
	 * @param integer|string $status integer range 0 to 254, string printed on exit
	 * @return void
	 */
	protected function _stop($status = 0) {
		exit($status);
	}

}

?>