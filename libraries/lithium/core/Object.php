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
 * Base class in Lithium hierarchy, from which all other dynamic classes inherit.
 *
 * Options defined:
 *  - 'init' `boolean` Controls constructor behaviour for calling the _init method. If false the
 *    method is not called, otherwise it is. Defaults to true.
 *
 */
class Object {

	/**
	 * Stores configuration information for object instances at time of construction.
	 * **Do not override.** Pass any additional variables to `parent::__construct()`.
	 *
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Holds an array of values that should be processed on initialisation.
	 *
	 * @var array
	 */
	protected $_autoConfig = array();

	protected $_methodFilters = array();

	protected static $_parents = array();

	/**
	 * Initialises properties, unless supplied configuration options change the default behaviour.
	 *
	 * @param array $config
	 * @return object
	 */
	public function __construct($config = array()) {
		$defaults = array('init' => true);
		$this->_config = (array) $config + $defaults;

		if ($this->_config['init']) {
			$this->_init();
		}
	}

	/**
	 * Initializer function. Called by constructor unless constructor `'init'` flag set to false.
	 * May be used for testing purposes, where objects need to be manipulated in an un-initialized
	 * state.
	 *
	 * @return void
	 */
	protected function _init() {
		if ($this->_autoConfig === array(true)) {
			$this->_autoConfig = array_keys($this->_config);
		}

		foreach ($this->_autoConfig as $key => $flag) {
			if (is_numeric($key)) {
				$key = $flag;
				$flag = null;
			}

			if (!isset($this->_config[$key])) {
				continue;
			}

			switch ($flag) {
				case 'merge':
					$this->{"_$key"} = $this->_config[$key] + $this->{"_$key"};
				break;
				case 'call':
					$this->{$key}($this->_config[$key]);
				break;
				default:
					$this->{"_$key"} = $this->_config[$key];
				break;
			}
		}
	}

	/**
	 * Apply a closure to a method of the current object instance.
	 *
	 * @param mixed $method The name of the method to apply the closure to. Can either be a single
	 *        method name as a string, or an array of method names.
	 * @param closure $closure The closure that is used to filter the method(s).
	 * @return void
	 * @see lithium\core\Object::_filter()
	 * @see lithium\util\collection\Filters
	 */
	public function applyFilter($method, $closure = null) {
		foreach ((array) $method as $m) {
			if (!isset($this->_methodFilters[$m])) {
				$this->_methodFilters[$m] = array();
			}
			$this->_methodFilters[$m][] = $closure;
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
	 * Executes a set of filters against a method by taking a method's main implementation as a
	 * callback, and iteratively wrapping the filters around it.
	 *
	 * @param string|array $method The name of the method being executed, or an array containing
	 *        the name of the class that defined the method, and the method name.
	 * @param array $params An associative array containing all the parameters passed into
	 *        the method.
	 * @param Closure $callback The method's implementation, wrapped in a closure.
	 * @param array $filters Additional filters to apply to the method for this call only.
	 * @return mixed
	 * @see lithium\util\collection\Filters
	 */
	protected function _filter($method, $params, $callback, $filters = array()) {
		list($class, $method) = explode('::', $method);

		if (empty($this->_methodFilters[$method]) && empty($filters)) {
			return $callback->__invoke($this, $params, null);
		}

		$f = isset($this->_methodFilters[$method]) ? $this->_methodFilters[$method] : array();
		$items = array_merge($f, $filters, array($callback));
		return Filters::run($this, $params, compact('items', 'class', 'method'));
	}

	protected static function _parents() {
		$class = get_called_class();

		if (!isset(self::$_parents[$class])) {
			self::$_parents[$class] = class_parents($class);
		}
		return self::$_parents[$class];
	}

	/**
	 * Exit immediately.  Primarily used for overrides during testing.
	 *
	 * @return void
	 */
	protected function _stop() {
		exit();
	}
}

?>