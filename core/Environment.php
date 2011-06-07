<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use lithium\util\Set;

/**
 * The `Environment` class allows you to manage multiple configurations for your application,
 * depending on the context within which it is running, i.e. development, test or production.
 *
 * While those three environments are the most common, you can create any arbitrary environment
 * with any set of configuration, for example:
 *
 * {{{ embed:lithium\tests\cases\core\EnvironmentTest::testSetAndGetCurrentEnvironment(1-3)}}}
 *
 * You can then retrieve the configurations using the key name. The correct configuration is
 * returned, automatically accounting for the current environment:
 *
 * {{{ embed:lithium\tests\cases\core\EnvironmentTest::testSetAndGetCurrentEnvironment(15-15)}}}
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
 * When the cache configuration is accessed in the application's code, the correct configuration is
 * automatically used:
 * {{{
 * $user = User::find($request->id);
 * Cache::write('userData', "User.{$request->id}", $user->data(), '+5 days');
 * }}}
 *
 * In this configuration, the above example will automatically send cache writes to the file system
 * during local development, and to a [ memcache](http://memcached.org/) server in production.
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
 */
class Environment {

	protected static $_configurations = array(
		'production' => array(),
		'development' => array(),
		'test' => array()
	);

	/**
	 * Holds the name of the current environment under which the application is running. Set by
	 * passing a `Request` object or `$_SERVER` or `$_ENV` array into `Environment::set()` (which
	 * in turn passes this on to the _detector_ used to determine the correct environment). Can be
	 * tested or retrieved using `Environment::is()` or `Environment::get()`.
	 *
	 * @see lithium\correct\Environment::set()
	 * @see lithium\correct\Environment::is()
	 * @see lithium\correct\Environment::get()
	 * @var string
	 */
	protected static $_current = '';

	/**
	 * If `Environment::is()` is used to assign a custom closure for environment detection, a copy
	 * is kept in `$_detector`. Otherwise, `$_detector` is `null`, and the hard-coded detector is
	 * used.
	 *
	 * @see lithium\core\Environment::_detector()
	 * @see lithium\core\Environment::is()
	 * @var object
	 */
	protected static $_detector = null;

	/**
	 * Resets the `Environment` class to its default state, including unsetting the current
	 * environment, removing any environment-specific configurations, and removing the custom
	 * environment detector, if any has been specified.
	 *
	 * @return void
	 */
	public static function reset() {
		static::$_current = '';
		static::$_detector = null;
		static::$_configurations = array(
			'production' => array(),
			'development' => array(),
			'test' => array()
		);
	}

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

	/**
	 * Gets the current environment name, a setting associated with the current environment, or the
	 * entire configuration array for the current environment.
	 *
	 * @param string $name The name of the environment setting to retrieve, or the name of an
	 *               environment, if that environment's entire configuration is to be retrieved. If
	 *               retrieving the current environment name, `$name` should not be passed.
	 * @return mixed If `$name` is unspecified, returns the name of the current environment name as
	 *         a string (i.e. `'production'`). If an environment name is specified, returns that
	 *         environment's entire configuration as an array.
	 */
	public static function get($name = null) {
		$cur = static::$_current;

		if (!$name) {
			return $cur;
		}
		if ($name === true) {
			return isset(static::$_configurations[$cur]) ? static::$_configurations[$cur] : null;
		}
		if (isset(static::$_configurations[$name])) {
			return static::_processDotPath($name, static::$_configurations);
		}
		if (!isset(static::$_configurations[$cur])) {
			return static::_processDotPath($name, static::$_configurations);
		}

		return static::_processDotPath($name, static::$_configurations[$cur]);
	}

	protected static function _processDotPath($path, &$arrayPointer) {
		if (isset($arrayPointer[$path])) {
			return $arrayPointer[$path];
		}
		if (strpos($path, '.') === false) {
			return null;
		}
		$pathKeys = explode('.', $path);
		foreach ($pathKeys as $pathKey) {
			if(!isset($arrayPointer[$pathKey])) {
				return false;
			}
			$arrayPointer = &$arrayPointer[$pathKey];
		}
		return $arrayPointer;
	}

	/**
	 * Creates, modifies or switches to an existing environment configuration. To create a new
	 * configuration, or to update an existing configuration, pass an environment name and an array
	 * that defines its configuration:
	 * {{{ embed:lithium\tests\cases\core\EnvironmentTest::testModifyEnvironmentConfig(1-1) }}}
	 *
	 * You can then add to an existing configuration by calling the `set()` method again with the
	 * same environment name:
	 * {{{ embed:lithium\tests\cases\core\EnvironmentTest::testModifyEnvironmentConfig(6-6) }}}
	 *
	 * The settings for the environment will then be the aggregate of all `set()` calls:
	 * {{{ embed:lithium\tests\cases\core\EnvironmentTest::testModifyEnvironmentConfig(7-7) }}}
	 *
	 * The `set()` method can also be called to manually set which environment to operate in:
	 * {{{ embed:lithium\tests\cases\core\EnvironmentTest::testSetAndGetCurrentEnvironment(5-5) }}}
	 *
	 * Finally, `set()` can accept a `Request` object, or the `$_SERVER` or `$_ENV` superglobals, to
	 * automatically detect the correct environment.
	 *
	 * {{{ embed:lithium\tests\cases\core\EnvironmentTest::testEnvironmentDetection(9-10) }}}
	 *
	 * For more information on defining custom rules to automatically detect your application's
	 * environment, see the documentation for `Environment::is()`.
	 *
	 * @see lithium\http\Request
	 * @see lithium\core\Environment::is()
	 * @param mixed $env The name of the environment you wish to create, update or switch to
	 *              (string), or a `Request` object or `$_SERVER` / `$_ENV` array used to detect
	 *              (and switch to) the application's current environment.
	 * @param array $config If creating or updating a configuration, accepts an array of settings.
	 *              If the environment name specified in `$env` already exists, the values in
	 *              `$config` will be recursively merged with any pre-existing settings.
	 * @return array If creating or updating a configuration, returns an array of the environment's
	 *               settings. If updating an existing configuration, this will be the newly-applied
	 *               configuration merged with the pre-existing values. If setting the environment
	 *               itself (i.e. `$config` is unspecified), returns `null`.
	 */
	public static function set($env, $config = null) {
		if (is_null($config)) {
			switch (true) {
				case is_object($env) || is_array($env):
					static::$_current = static::_detector()->__invoke($env);
				break;
				case isset(static::$_configurations[$env]):
					static::$_current = $env;
				break;
			}
			return;
		}
		$env = ($env === true) ? static::$_current : $env;
		$base = isset(static::$_configurations[$env]) ? static::$_configurations[$env] : array();
		return static::$_configurations[$env] = Set::merge($base, $config);
	}

	/**
	 * Accessor method for `Environment::$_detector`. If `$_detector` is unset, returns the default
	 * detector built into the class. For more information on setting and using `$_detector`, see
	 * the documentation for `Environment::is()`. The `_detector()` method is called at the
	 * beginning of the application's life-cycle, when `Environment::set()` is passed either an
	 * instance of a `Request` object, or the `$_SERVER` or `$_ENV` array. This object (or array)
	 * is then passed onto `$_detector`, which returns the correct environment.
	 *
	 * @see lithium\core\Environment::is()
	 * @see lithium\core\Environment::set()
	 * @see lithium\core\Environment::$_detector
	 * @return object Returns a callable object (anonymous function) which detects the application's
	 *         current environment.
	 */
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