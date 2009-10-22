<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use \Exception;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \lithium\util\String;

class Libraries {

	/**
	 * The list of class libraries registered with the class loader.
	 *
	 * @var array
	 */
	protected static $_configurations = array();

	/**
	 * Contains a cascading list of search path templates, indexed by base object type. Used by
	 * `Libraries::locate()` to perform service location. This allows new types of objects (i.e.
	 * models, helpers, cache adapters and data sources) to be automatically 'discovered' when you
	 * register a new vendor library or plugin (using `Libraries::add()`).
	 *
	 * Because paths are checked in the order in which they appear, path templates should be
	 * specified from most-specific to least-specific. See the `locate()` method for usage examples.
	 *
	 * @var array
	 * @see lithium\core\Libraries::locate()
	 */
	protected static $_classPaths = array(
		'adapters' => array(
			'{:library}\extensions\adapters\{:namespace}\{:class}\{:name}',
			'{:library}\extensions\adapters\{:class}\{:name}',
			'{:library}\{:namespace}\{:class}\adapters\{:name}' => array('libraries' => 'lithium')
		),
		'commands' => array(
			'{:library}\extensions\commands\{:class}\{:name}',
			'{:library}\extensions\commands\{:name}',
			'{:library}\console\commands\{:name}' => array('libraries' => 'lithium')
		),
		'controllers' => array(
			'{:library}\controllers\{:name}Controller'
		),
		'dataSources' => array(
			'{:library}\extensions\data\source\{:class}\adapter\{:name}',
			'{:library}\data\source\{:class}\adapter\{:name}' => array('libraries' => 'lithium'),
			'{:library}\extensions\data\source\{:name}',
			'{:library}\data\source\{:name}' => array('libraries' => 'lithium')
		),
		'helpers' => array(
			'{:library}\extensions\helpers\{:name}',
			'{:library}\template\helpers\{:name}' => array('libraries' => 'lithium')
		),
		'models' => array(
			'{:library}\models\{:name}'
		),
		'sockets' => array(
			'{:library}\extensions\sockets\{:name}',
			'{:library}\{:class}\socket\{:name}' => array('libraries' => 'lithium')
		),
		'testFilters' => array(
			'{:library}\tests\filters\{:name}',
			'{:library}\test\filters\{:name}' => array('libraries' => 'lithium')
		)
	);

	/**
	 * @todo Implement in add()
	 */
	protected static $_libraryPaths = array(
		'{:app}/libraries/{:name}',
		'{:root}/plugins/{:name}'
	);

	protected static $_pluginPaths = array(
		'{:app}/libraries/plugins/{:name}',
		'{:root}/plugins/{:name}'
	);

	/**
	 * Holds cached class paths generated and used by lithium\core\Libraries::load().
	 *
	 * @var array
	 * @see lithium\core\Libraries::load()
	 */
	protected static $_cachedPaths = array();

	/**
	 * Adds a class library from which files can be loaded
	 *
	 * @param string $name Library name, i.e. 'app', 'lithium', 'pear' or 'solar'.
	 * @param array $options Specifies where the library is in the filesystem, and how classes
	 *              should be loaded from it.  Allowed keys are:
	 *              - 'path': The directory containing the library.
	 *              - 'loader': An auto-loader method associated with the library, if any
	 *              - 'bootstrap': A file path (relative to 'path') to a bootstrap script that
	 *                should be run when the library is added.
	 *              - 'prefix': The class prefix this library uses, i.e. 'lithium\', 'Zend_'
	 *                or 'Solar_'.
	 *              - 'suffix': Gets tacked on to the end of the file name.  For example, most
	 *                libraries end classes in '.php', but some use '.class.php', or '.inc.php'.
	 *              - 'transform': Defines a custom way to transform a class name into its
	 *                corresponding file path.  Accepts either an array of two strings which
	 *                are interpreted as the pattern and replacement for a regex, or an
	 *                anonymous function, which receives the class name as a parameter, and
	 *                returns a file path as output.
	 *              - 'defer': If true, indicates that, when locating classes, this library should
	 *                defer to other libraries in order of preference.
	 *              - 'includePath': If true, appends the absolutely-resolved value of `'path'` to
	 *                the PHP include path.
	 * @return array Returns the resulting set of options created for this library.
	 */
	public static function add($name, $config = array()) {
		$defaults = array(
			'path' => LITHIUM_LIBRARY_PATH . '/' . $name,
			'loader' => null,
			'prefix' => $name . "\\",
			'suffix' => '.php',
			'transform' => null,
			'bootstrap' => null,
			'defer' => false,
			'includePath' => false
		);

		switch ($name) {
			case 'app':
				$defaults['path'] = LITHIUM_APP_PATH;
				$defaults['bootstrap'] = 'config/switchboard.php';
			break;
			case 'lithium':
				$defaults['loader'] = 'lithium\core\Libraries::load';
				$defaults['defer'] = true;
			break;
		}

		if ($name === 'plugin') {
			return static::_addPlugins((array)$config);
		}
		static::$_configurations[$name] = ((array)$config += $defaults);

		if ($config['includePath']) {
			$path = ($config['includePath'] === true) ? $config['path'] : $config['includePath'];
			set_include_path(get_include_path() . ':' . $path);
		}

		if (!empty($config['bootstrap'])) {
			if ($config['bootstrap'] === true) {
				$config['bootstrap'] = 'config/bootstrap.php';
			}
			require $config['path'] . '/' . $config['bootstrap'];
		}

		if (!empty($config['loader'])) {
			spl_autoload_register($config['loader']);
		}
		return $config;
	}

	public static function get($name = null) {
		if (empty($name)) {
			return static::$_configurations;
		}
		return isset(static::$_configurations[$name]) ? static::$_configurations[$name] : null;
	}

	/**
	 * Removes a registered library, and unregister's the library's autoloader, if it has one.
	 *
	 * @param mixed $name A string or array of library names indicating the libraries you wish to
	 *              remove, i.e. `'app'` or `'lithium'`.  This can also be used to unload plugins by
	 *              name.
	 * @return void
	 */
	public static function remove($name) {
		foreach ((array)$name as $library) {
			if (isset(static::$_configurations[$library])) {
				if (static::$_configurations[$library]['loader']) {
					spl_autoload_unregister(static::$_configurations[$library]['loader']);
				}
				unset(static::$_configurations[$library]);
			}
		}
	}

	/**
	 * Finds the classes in a library/namespace/folder
	 *
	 * @todo Tie this into how path() is implemented
	 * @param string $library
	 * @param string $options
	 * @return array
	 */
	public static function find($library, $options = array()) {
		if ($library === true) {
			$libs = array();

			foreach (array_keys(static::$_configurations) as $library) {
				$libs = array_merge($libs, static::find($library, $options));
			}
			return $libs;
		}

		$defaults = array(
			'recursive' => false,
			'filter' => '/^(\w+)?(\\\\[a-z0-9_]+)+\\\\[A-Z][a-zA-Z0-9]+$/',
			'exclude' => '',
			'path' => '',
			'format' => false,
			'namespaces' => false
		);
		$options += $defaults;

		if ($options['namespaces'] && $options['filter'] == $defaults['filter']) {
			$options['filter'] = false;
		}

		if (!isset(static::$_configurations[$library])) {
			return null;
		}
		$config = static::$_configurations[$library];
		$path = rtrim($config['path'] . $options['path'], '/');
		$filter = '/^.+\/[A-Za-z0-9_]+$|^.*' . preg_quote($config['suffix'], '/') . '/';

		$search = function($path) use ($filter, $options, $config) {
			return preg_grep($filter, glob(
				$path . '/*' . ($options['namespaces'] ? '' : $config['suffix'])
			));
		};
		$libs = $search($path);

		if ($options['recursive']) {
			$dirs = $queue = array_diff(glob($path . '/*', GLOB_ONLYDIR), $libs);

			while ($queue) {
				$dir = array_pop($queue);

				if (!is_dir($dir)) {
					continue;
				}
				$libs = array_merge($libs, $search($dir));
				$queue = array_merge($queue, array_diff(glob($dir . '/*', GLOB_ONLYDIR), $libs));
			}
		}
		$trim = array(strlen($config['path']) + 1, strlen($config['suffix']));

		if ($options['format'] != 'files') {
			foreach ($libs as $i => $file) {
				$rTrim = strpos($file, $config['suffix']) !== false ? -$trim[1] : 9999;
				$file = preg_split('/[\/\\\\]/', substr($file, $trim[0], $rTrim));
				$libs[$i] = $config['prefix'] . join('\\', $file);
			}
		}

		$exclude = $options['exclude'];
		$libs = $exclude ? preg_grep($exclude, $libs, PREG_GREP_INVERT) : $libs;
		$libs = $options['filter'] ? preg_grep($options['filter'], $libs) : $libs;
		return array_values($libs);
	}

	/**
	 * Get the corresponding physical file path for a class name.
	 *
	 * @param string $class
	 * @param string $options
	 * @return array
	 */
	public static function path($class, $options = array()) {
		if (array_key_exists($class, static::$_cachedPaths)) {
			return static::$_cachedPaths[$class];
		}
		$class = ($class[0] == '\\') ? substr($class, 1) : $class;

		foreach (static::$_configurations as $name => $options) {
			if (strpos($class, $options['prefix']) !== 0) {
				continue;
			}

			if (!empty($options['transform'])) {
				if (is_object($options['transform'])) {
					return $options['transform']($class, $options);
				}
				list($match, $replace) = $options['transform'];
				return preg_replace($match, $replace, $class);
			}
			$path = str_replace("\\", '/', substr($class, strlen($options['prefix'])));
			return $options['path'] . '/' . $path . $options['suffix'];
		}
	}

	/**
	 * Performs service location for an object of a specific type.
	 *
	 * @param string $type
	 * @param string $name
	 * @return string
	 * @see lithium\core\Libraries::$_classPaths
	 */
	public static function locate($type, $name = null, $options = array()) {
		if (strpos($name, '\\') !== false) {
			return $name;
		}
		$ident = $name ? $type . '.' . $name : $type;

		if (isset(static::$_cachedPaths[$ident])) {
			return static::$_cachedPaths[$ident];
		}

		if (strpos($type, '.')) {
			$parts = explode('.', $type);
			$type = array_shift($parts);

			switch (count($parts)) {
				case 1:
					list($class) = $parts;
				break;
				case 1:
				case 2:
					list($namespace, $class) = $parts;
				break;
				default:
					$class = array_pop($parts);
					$namespace = join('\\', $parts);
				break;
			}
		}

		if (!isset(static::$_classPaths[$type])) {
			return null;
		}

		if (is_null($name)) {
			return static::_locateAll($type);
		}

		$params = compact('type', 'namespace', 'class', 'name');
		$paths = static::$_classPaths[$type];

		if (strpos($name, '.')) {
			list($params['library'], $params['name']) = explode('.', $name);
			$params['library'][0] = strtolower($params['library'][0]);

			$result = static::_locateDeferred(null, $paths, $params, $options + array(
				'library' => $params['library']
			));
			return static::$_cachedPaths[$ident] = $result;
		}

		if ($result = static::_locateDeferred(false, $paths, $params, $options)) {
			return (static::$_cachedPaths[$ident] = $result);
		}
		if ($result = static::_locateDeferred(true, $paths, $params, $options)) {
			return (static::$_cachedPaths[$ident] = $result);
		}
	}

	/**
	 * Loads the class definition specified by `$class`. Also calls the __init() method on the
	 * class, if defined.  Looks through the list of libraries defined in $_configurations, which
	 * are added through lithium\core\Libraries::add().
	 *
	 * @param string $class The fully-namespaced (where applicable) name of the class to load.
	 * @see lithium\core\Libraries::add()
	 * @see lithium\core\Libraries::path()
	 * @return void
	 */
	public static function load($class, $require = false) {
		if (($path = static::path($class)) && is_readable($path) && include $path) {
			static::$_cachedPaths[$class] = $path;
			method_exists($class, '__init') ? $class::__init() : null;
		} elseif ($require) {
			throw new Exception("Failed to load {$class} from {$path}");
		}
	}

	protected static function _locateAll($type, $options = array()) {
		$defaults = array('libraries' => null);
		$options += $defaults;
		$type = explode('.', $type);

		$paths = $classes = array();
		$pathTemplates = static::$_classPaths[current($type)];
		$libraries = $options['libraries'] ?: array_keys(static::$_configurations);

		foreach ($libraries as $library) {
			$config = static::$_configurations[$library];

			foreach ($pathTemplates as $template => $tplOpts) {
				if (is_int($template)) {
					$template = $tplOpts;
					$tplOpts = array();
				}
				$scope = $options['libraries'] ? (array)$options['libraries'] : null;

				if ($scope && !in_array($library, $scope)) {
					continue;
				}
				$params['library'] = $config['path'];
				$path = str_replace('\\', '/', preg_replace('/\\\{:\w+}/', '', String::insert(
					$template, $params, array('escape' => '/')
				)));

				if (is_dir($path)) {
					$paths[$path] = $library;
				}
			}
		}

		foreach ($paths as $path => $library) {
			$config = static::$_configurations[$library];
			$suffix = '/' . preg_quote($config['suffix'], '/') . '$/';
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $i) {
				if ($i->isFile() && preg_match($suffix, $i->getFilename())) {
					$trim = array(strlen($config['path']) + 1, strlen($config['suffix']));
					$file = substr($i->getPathname(), $trim[0], -$trim[1]);
					$classes[] = $config['prefix'] . str_replace('/', '\\', $file);
				}
			}
		}
		return $classes;
	}

	/**
	 * Performs service location lookups by library, based on the library's `'defer'` flag.
	 * Libraries with `'defer'` set to `true` will be searched last when looking up services.
	 *
	 * @param boolean $defer A boolean flag indicating which libraries to search, either the ones
	 *                with the `'defer'` flag set, or the ones without.
	 * @param array $paths The list of paths to be searched for the given service (class).  These
	 *              are defined in `lithium\core\Libraries::$_classPaths`, and are organized by class
	 *              type.
	 * @param array $params The list of insert parameters to be injected into each path format
	 *              string when searching for classes.
	 * @param array $options
	 * @return string Returns a class path as a string if a given class is found, or null if no
	 *                class in any path matching any of the parameters is located.
	 * @see lithium\core\Libraries::$_classPaths
	 * @see lithium\core\Libraries::locate()
	 */
	protected static function _locateDeferred($defer, $paths, $params, $options = array()) {
		if (isset($options['library'])) {
			$libraries = (array)$options['library'];
			$libraries = array_intersect_key(
				static::$_configurations,
				array_combine($libraries, array_fill(0, count($libraries), null))
			);
		} else {
			$libraries = static::$_configurations;
		}

		foreach ($libraries as $library => $config) {
			if ($config['defer'] !== $defer && $defer !== null) {
				continue;
			}

			foreach ($paths as $pathTemplate => $options) {
				if (is_int($pathTemplate)) {
					$pathTemplate = $options;
					$options = array();
				}
				$scope = isset($options['libraries']) ? (array)$options['libraries'] : null;

				if ($scope && !in_array($library, $scope)) {
					continue;
				}
				$params['library'] = $library;
				$classPath = String::insert($pathTemplate, $params);

				if (file_exists(Libraries::path($classPath))) {
					return $classPath;
				}
			}
		}
	}

	/**
	 * Register a Lithium plugin
	 *
	 * @param string $plugins
	 * @param string $options
	 * @return void
	 */
	protected static function _addPlugins($plugins) {
		$defaults = array('bootstrap' => null, 'route' => true);
		$params = array('app' => LITHIUM_APP_PATH, 'root' => LITHIUM_LIBRARY_PATH);
		$result = array();

		foreach ($plugins as $name => $options) {
			if (is_int($name)) {
				$name = $options;
				$options = array();
			}
			$params = compact('name') + $params;

			if (!isset($options['path'])) {
				foreach (static::$_pluginPaths as $path) {
					if (is_dir($dir = String::insert($path, $params))) {
						$options['path'] = $dir;
						break;
					}
				}
			}
			$plugin = static::add($name, $options + $defaults);

			if ($plugin['route']) {
				$defaultRoutes = $plugin['path'] . '/config/routes.php';
				$route = ($plugin['route'] === true) ? $defaultRoutes : $plugin['route'];
				!file_exists($route) ?: include $route;
			}
			$result[$name] = $plugin;
		}
		return $result;
	}
}

if (!defined('LITHIUM_LIBRARY_PATH')) {
	define('LITHIUM_LIBRARY_PATH', dirname(__DIR__));
}

?>