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
 * The `Environment` class allows you to manage multiple configurations for your application,
 * depending on the context within which it is running, i.e. development, test or production.
 *
 * While those three environments are the most common, you can create any arbitrary environment
 * with any set of configuration, for example:
 *
 * {{{
 * Environment::set('production',  array('foo' => 'bar'));
 * Environment::set('staging',     array('foo' => 'baz'));
 * Environment::set('development', array('foo' => 'dib'));
 * }}}
 *
 * `Environment` also works with subclasses of `Adaptable`, allowing you to maintain separate
 * configurations for database servers, cache adapters, and other environment-specific classes, for
 * example:
 * {{{
 * Connections::add('default', array(
 * 	'production' => array(
 * 		'type'     => 'database',
 * 		'adapter'  => 'MySql',
 * 		'host'     => 'db1.application.local',
 * 		'login'    => 'secure',
 * 		'password' => 'secret',
 * 		'database' => 'app-production'
 * 	),
 * 	'development' => array(
 * 		'type'     => 'database',
 * 		'adapter'  => 'MySql',
 * 		'host'     => 'localhost',
 * 		'login'    => 'root',
 * 		'password' => '',
 * 		'database' => 'app'
 * 	)
 * ));
 * }}}
 *
 * This allows the database connection named `'default'` to be connected to a local database in
 * development, and a production database in production. You can define environment-specific
 * configurations for caching, logging, even session storage, i.e.:
 * {{{
 * Cache::config(array(
 * 	'userData' => array(
 * 		'development' => array('adapter' => 'File'),
 * 		'production' => array('adapter' => 'Memcache')
 * 	)
 * ));
 * }}}
 *
 * When writing classes that connect to other external resources, you can automatically take
 * advantage of environment-specific configurations by extending `Adaptable` and implementing
 * your resource-handling functionality in adapter classes.
 *
 * In addition to managing your environment-specific configurations, `Environment` will also help
 * you by automatically detecting which environment your application is running in. For additional
 * information, see the documentation for `Environment::is()`.
 *
 * @see lithium\core\Adaptable
 * @see lithium\core\Environment::is()
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

	/**
	 * A simple boolean detector that can be used to test which environment the application is
	 * running under. For example `Environment::is('development')` will return `true` if
	 * `'development'` is, in fact, the current environment.
	 *
	 * This method also handles how the environment is detected at the beginning of the request.
	 * While the default detection rules are very simple (if the `'SERVER_ADDR'` variable is set to
	 * `127.0.0.1`, the environment is assumed to be `'development'`, or if the string `'test'` is
	 * found anywhere in the host name, it is assumed to be `'test'`, and in all other cases it
	 * is assumed to be `'production'`), you can define your own detection rule set easily using a
	 * closure that accepts an instance of the `Request` object, and returns the name of the correct
	 * environment, as in the following example:
	 * {{{ embed:lithium\tests\cases\core\EnvironmentTest::testCustomDetector(1-9)}}}
	 *
	 * In the above example, the user-specified closure takes in a `Request` object, and using the
	 * server data which it encapsulates, returns the correct environment name as a string.
	 *
	 * @param mixed $detect Either the name of an environment to check against the current, i.e.
	 *              `'development'` or `'production'`, or a closure which `Environment` will use
	 *              to determine the current environment name.
	 * @return boolean If `$detect` is a string, returns `true` if the current environment matches
	 *         the value of `$detect`, or `false` if no match. If used to set a custom detector,
	 *         returns `null`.
	 */
	public static function is($detect) {
		if (is_callable($detect)) {
			static::$_detector = $detect;
		}
		return (static::$_current == $detect);
	}

	public static function get($name = null, $key = null) {
		if (empty($name) && empty($key)) {
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