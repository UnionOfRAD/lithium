<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use \lithium\util\Set;

/**
 * Environment
 *
 */
class Environment {

	protected static $_configurations = array(
		'base' => array(),
		'production' => array(
			'inherit' => 'base'
		),
		'development' => array(
			'inherit' => 'base'
		),
		'test' => array(
			'inherit' => 'development'
		)
	);

	protected static $_current = null;

	protected static $_detector = null;

	public static function is($detect = null) {
		if (is_callable($detect)) {
			static::$_detector = $detect;
		} elseif (is_string($detect)) {
			return (static::$_current == $detect);
		}
		return static::$_current;
	}

	public static function get($name = null, $path = null) {
		if (empty($name) && empty($path)) {
			return static::$_current;
		}
		if (!isset(static::$_configurations[$name])) {
			return null;
		}
		return static::$_configurations[$name];
	}

	/**
	 * Creates, modifies or switches to an existing configuration.
	 *
	 * @param mixed $env
	 * @param array $config
	 * @return array
	 */
	public static function set($env, $config = null) {
		if (is_null($config)) {
			switch(true) {
				case is_object($env) || is_array($env):
					static::$_current = static::_detector()->__invoke($env);
				break;
				case isset(static::$_configurations[$env]):
					static::$_current = $env;
				break;
			}
			return;
		}

		if (isset(static::$_configurations[$env]) && $base = static::$_configurations[$env]) {
			return static::$_configurations[$env] = Set::merge($base, $config);
		}
	}

	protected static function _detector() {
		return static::$_detector ?: function($request) {
			switch (true) {
				case (in_array($request->env('SERVER_ADDR'), array('::1', '127.0.0.1'))):
					return 'development';
				case (preg_match('/^test/', $request->env('HTTP_HOST'))):
					return 'test';
				default:
					return 'production';
			}
		};
	}
}

?>