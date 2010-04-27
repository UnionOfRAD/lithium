<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\core;

use \Exception;
use \lithium\util\String;
use \InvalidArgumentException;

/**
 * Manages all aspects of class and file location, naming and mapping. Implements auto-loading for
 * the Lithium core, as well as all applications, plugins and vendor libraries registered.
 * Typically, libraries and plugins are registered in `app/config/bootstrap/libraries.php`.
 *
 * By convention, vendor libraries are typically located in `app/libraries` or `/libraries`, and
 * plugins are located in `app/libraries/plugins` or `/libraries/plugins`. By default, `Libraries`
 * will use its own autoloader for all plugins and vendor libraries, but can be configured to use
 * others on a case-by-case basis.
 *
 * `Libraries` also handles service location. Various 'types' of classes can be defined by name,
 * using _class patterns_, which define conventions for organizing classes, i.e. `'models'` is
 * `'{:library}\models\{:name}'`, which will find a model class in any registered app, plugin or
 * vendor library that follows that path (namespace) convention. You can find classes by name (see
 * `locate()` for more information on class-locating precedence), or find all models in all
 * registered libraries (apps / plugins / vendor libraries, etc). For more information on modifying
 * the default class organization, or defining your own class types, see the `paths()` method.
 *
 * #### Auto-loading classes
 *
 * Lithium defines several rules, conventions and recommendations for naming and organizing classes.
 * The autoloader itself conforms to the [PHP Interoperability Group's draft
 * specification](http://groups.google.com/group/php-standards/web/psr-0-final-proposal). While the
 * autoloader will load any classes conforming to that specification, Lithium itself follows
 * additional constraints, which are also recommended for Lithium applications, libraries and
 * extensions, and are as follows:
 *
 * - Each library must exist in a top-level vendor namespace
 * - Each top-level vendor namespace must define a set of sub-packages, and should not contain
 *   classes
 * - Namespace names must be lowercased and under_scored
 * - Class names must be CamelCased
 *
 * Any libraries registered by calling `Libraries::add()` which follow the autoloader's will have
 * their classes automatically loaded when referenced.
 *
 * @see lithium\core\Libraries::add()
 * @see lithium\core\Libraries::locate()
 * @see lithium\core\Libraries::paths()
 */
class Libraries {

	/**
	 * The list of class libraries registered with the class loader.
	 *
	 * @var array
	 */
	protected static $_configurations = array();

	/**
	 * Contains a cascading list of search path templates, indexed by base object type.
	 *
	 * Used by `Libraries::locate()` to perform service location. This allows new types of
	 * objects (i.e. models, helpers, cache adapters and data sources) to be automatically
	 * 'discovered' when you register a new vendor library or plugin (using `Libraries::add()`).
	 *
	 * Because paths are checked in the order in which they appear, path templates should be
	 * specified from most-specific to least-specific. See the `locate()` method for usage examples.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @see lithium\core\Libraries::paths()
	 * @var array
	 */
	protected static $_paths = array(
		'adapter' => array(
			'{:library}\extensions\adapter\{:namespace}\{:class}\{:name}',
			'{:library}\extensions\adapter\{:class}\{:name}',
			'{:library}\{:namespace}\{:class}\adapter\{:name}' => array('libraries' => 'lithium')
		),
		'strategy' => array(
			'{:library}\extensions\strategy\{:namespace}\{:class}\{:name}',
			'{:library}\{:namespace}\{:class}\strategy\{:name}' => array('libraries' => 'lithium')
		),
		'command' => array(
			'{:library}\extensions\command\{:namespace}\{:class}\{:name}',
			'{:library}\console\command\{:namespace}\{:class}\{:name}' => array(
				'libraries' => 'lithium'
			),
		),
		'controllers' => array(
			'{:library}\controllers\{:name}Controller'
		),
		'data' => array(
			'{:library}\extensions\data\{:namespace}\{:class}\{:name}',
			'{:library}\data\{:namespace}\{:class}\{:name}' => array('libraries' => 'lithium')
		),
		'helper' => array(
			'{:library}\extensions\helper\{:name}',
			'{:library}\template\helper\{:name}' => array('libraries' => 'lithium')
		),
		'libraries' => array(
			'{:app}/libraries/{:name}',
			'{:root}/{:name}'
		),
		'models' => '{:library}\models\{:name}',
		'test' => array(
			'{:library}\extensions\test\{:namespace}\{:class}\{:name}',
			'{:library}\test\{:namespace}\{:class}\{:name}' => array('libraries' => 'lithium')
		),
		'tests' => array(
			'{:library}\tests\{:namespace}\{:class}\{:name}Test'
		)
	);

	protected static $_default;

	/**
	 * Holds cached class paths generated and used by `lithium\core\Libraries::load()`.
	 *
	 * @var array
	 * @see lithium\core\Libraries::load()
	 */
	protected static $_cachedPaths = array();

	/**
	 * Accessor method for the class path templates which `Libraries` uses to look up and load
	 * classes. Using this method, you can define your own types of classes, or modify the default
	 * organization of built-in class types.
	 *
	 * For example, in a queuing application, you can define a class type called `'job'`:
	 * {{{
	 * Libraries::paths(array('job' => '{:library}\extensions\job\{:name}'));
	 * }}}
	 *
	 * Then, any classes you add to the `extensions/job` directory in your application will be
	 * automatically detected when calling `Libraries::locate('job')`. Additionally, any matching
	 * classes in the `extensions/job` directory of any plugin or vendor library you add to your
	 * application will also be detected.
	 *
	 * Supposing you wanted to have the option of further organizing jobs by class type (some jobs
	 * are related to updating caches, others to sending notifications, etc.), you can specify
	 * mulitple paths per class type, with varying levels of specificity:
	 * {{{
	 * Libraries::paths(array('job' => array(
	 * 	'{:library}\extensions\job\{:class}\{:name}',
	 * 	'{:library}\extensions\job\{:name}'
	 * )));
	 * }}}
	 *
	 * This allows you to, for example, have two different classes called `Cleanup`. One may be
	 * located in `app\extensions\job\Cleanup`, while the other is in
	 * `app\extensions\job\cache\Cleanup`. Calling: {{{Libraries::locate('job');}}} will find
	 * both classes, while {{{Libraries::locate('job.cache');}}} will only find the second. You can
	 * also find individual jobs by name: {{{Libraries::locate('job', 'Cleanup');}}}
	 *
	 * See `Libraries::locate()` for more information on using built-in and user-defined paths to
	 * look up classes.
	 *
	 * In addition to adding custom class types, `paths()` allows you to redefine the naming and
	 * organization of existing types. For example, if you wished to reference your model classes
	 * as `app\models\PostModel` instead of `app\models\Post`, you can do the following:
	 * {{{Libraries::paths(array('models' => '{:library}\models\{:name}Model'));}}} Note, however,
	 * that this is a destructive, not an additive operation, and will replace any existing paths
	 * defined for that type. If you wish to add a search path for an existing type, you must do
	 * the following:
	 * {{{
	 * $existing = Libraries::paths('controllers');
	 * Libraries::paths(array('controller' => array_merge(
	 * 	array('{:library}\extensions\controllers\{:name}Controller'), (array) $existing
	 * )));
	 * }}}
	 *
	 * @see lithium\core\Libraries::locate()
	 * @see lithium\core\Libraries::$_paths
	 * @param mixed $path If `$path` is a string, returns the path(s) associated with that path
	 *              type, or `null` if no paths are defined for that type.
	 * @return mixed
	 */
	public static function paths($path = null) {
		if (empty($path)) {
			return static::$_paths;
		}
		if (is_string($path)) {
			return isset(static::$_paths[$path]) ? static::$_paths[$path] : null;
		}
		static::$_paths = array_merge(static::$_paths, (array) $path);
	}

	/**
	 * Adds a class library from which files can be loaded
	 *
	 * @param string $name Library name, i.e. `'app'`, `'lithium'`, `'pear'` or `'solar'`.
	 * @param array $config Specifies where the library is in the filesystem, and how classes
	 *        should be loaded from it.  Allowed keys are:
	 *        - `'bootstrap'`: A file path (relative to `'path'`) to a bootstrap script that should
	 *          be run when the library is added.
	 *        - `'defer'`: If true, indicates that, when locating classes, this library should
	 *          defer to other libraries in order of preference.
	 *        - `'includePath'`: If `true`, appends the absolutely-resolved value of `'path'` to
	 *          the PHP include path.
	 *        - `'loader'`: An auto-loader method associated with the library, if any.
	 *        - `'path'`: The directory containing the library.
	 *        - `'prefix'`: The class prefix this library uses, i.e. `'lithium\'`, `'Zend_'` or
	 *          `'Solar_'`.
	 *        - `'suffix'`: Gets appended to the end of the file name. For example, most libraries
	 *          end classes in `'.php'`, but some use `'.class.php'`, or `'.inc.php'`.
	 *        - `'transform'`: Defines a custom way to transform a class name into its
	 *          corresponding file path.  Accepts either an array of two strings which are
	 *          interpreted as the pattern and replacement for a regex, or an anonymous function,
	 *          which receives the class name as a parameter, and returns a file path as output.
	 * @return array Returns the resulting set of options created for this library.
	 */
	public static function add($name, array $config = array()) {
		$defaults = array(
			'path' => null,
			'prefix' => $name . "\\",
			'suffix' => '.php',
			'loader' => null,
			'includePath' => false,
			'transform' => null,
			'bootstrap' => true,
			'defer' => false,
			'default' => false
		);
		if ($name === 'lithium') {
			$defaults['defer'] = true;
			$defaults['bootstrap'] = false;
			$defaults['path'] = dirname(__DIR__);
			$defaults['loader'] = 'lithium\core\Libraries::load';
		}
		if (isset($config['default']) && $config['default']) {
			static::$_default = $name;
			$defaults['path'] = LITHIUM_APP_PATH;
			$defaults['bootstrap'] = false;
		}
		$config += $defaults;

		if (!$config['path']) {
			$params = compact('name') + array(
				'app' => LITHIUM_APP_PATH, 'root' => LITHIUM_LIBRARY_PATH
			);
			if (!$config['path'] = static::_locatePath('libraries', $params)) {
				throw new InvalidArgumentException("Library '{$name}' not found.");
			}
		}
		$config['path'] = str_replace('\\', '/', $config['path']);
		static::$_configurations[$name] = $config;

		if ($config['includePath']) {
			$path = ($config['includePath'] === true) ? $config['path'] : $config['includePath'];
			set_include_path(get_include_path() . PATH_SEPARATOR . $path);
		}
		if ($config['bootstrap'] === true) {
			$path = "{$config['path']}/config/bootstrap.php";
			$config['bootstrap'] = file_exists($path) ? 'config/bootstrap.php' : false;
		}
		if ($config['bootstrap']) {
			require "{$config['path']}/{$config['bootstrap']}";
		}
		if (!empty($config['loader'])) {
			spl_autoload_register($config['loader']);
		}
		return $config;
	}

	/**
	 * Returns configuration for given name.
	 *
	 * @param string $name Registered library to retrieve configuration for.
	 * @return array Retrieved configuration.
	 */
	public static function get($name = null) {
		if (empty($name)) {
			return static::$_configurations;
		}
		if ($name === true) {
			$name = static::$_default;
		}
		return isset(static::$_configurations[$name]) ? static::$_configurations[$name] : null;
	}

	/**
	 * Removes a registered library, and unregister's the library's autoloader, if it has one.
	 *
	 * @param mixed $name A string or array of library names indicating the libraries you wish to
	 *        remove, i.e. `'app'` or `'lithium'`. This can also be used to unload plugins by  name.
	 * @return void
	 */
	public static function remove($name) {
		foreach ((array) $name as $library) {
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
	 * @param string $library
	 * @param string $options
	 * @return array
	 */
	public static function find($library, array $options = array()) {
		$format = function ($file, $config) {
			$trim = array(strlen($config['path']) + 1, strlen($config['suffix']));
			$rTrim = strpos($file, $config['suffix']) !== false ? -$trim[1] : 9999;
			$file = preg_split('/[\/\\\\]/', substr($file, $trim[0], $rTrim));
			return $config['prefix'] . join('\\', $file);
		};

		$defaults = array(
			'path' => '', 'recursive' => false,
			'filter' => '/^(\w+)?(\\\\[a-z0-9_]+)+\\\\[A-Z][a-zA-Z0-9]+$/',
			'exclude' => '',
			'format' => $format,
			'namespaces' => false
		);
		$options += $defaults;
		$libs = array();

		if ($options['namespaces'] && $options['filter'] == $defaults['filter']) {
			$options['format'] = function($class, $config) use ($format, $defaults) {
				if (is_dir($class)) {
					return $format($class, $config);
				}
				if (preg_match($defaults['filter'], $class = $format($class, $config))) {
					return $class;
				}
			};
			$options['filter'] = false;
		}

		if ($library === true) {
			foreach (array_keys(static::$_configurations) as $library) {
				$libs = array_merge($libs, static::find($library, $options));
			}
			return $libs;
		}

		if (!isset(static::$_configurations[$library])) {
			return null;
		}

		$libs = static::_search(static::$_configurations[$library], $options);
		return array_values(array_filter($libs));
	}

	/**
	 * Loads the class definition specified by `$class`. Also calls the `__init()` method on the
	 * class, if defined.  Looks through the list of libraries defined in `$_configurations`, which
	 * are added through `lithium\core\Libraries::add()`.
	 *
	 * @see lithium\core\Libraries::add()
	 * @see lithium\core\Libraries::path()
	 *
	 * @param string $class The fully-namespaced (where applicable) name of the class to load.
	 * @param boolean $require Specifies whether the class must be loaded or considered an
	 *        exception. Defaults to `false`.
	 * @return void
	 */
	public static function load($class, $require = false) {
		$path = isset(static::$_cachedPaths[$class]) ? static::$_cachedPaths[$class] : null;
		$path = $path ?: static::path($class);

		if ($path && include $path) {
			static::$_cachedPaths[$class] = $path;
			method_exists($class, '__init') ? $class::__init() : null;
		} elseif ($require) {
			throw new Exception("Failed to load {$class} from {$path}");
		}
	}

	/**
	 * Get the corresponding physical file path for a class or namespace name.
	 *
	 * @param string $class The class name to locate the physical file for. If `$options['dirs']` is
	 *        set to `true`, `$class` may also be a namespace name, in which case the corresponding
	 *        directory will be located.
	 * @param array $options Options for converting `$class` to a phyiscal path:
	 *        - `'dirs'`: Defaults to `false`. If `true`, will attempt to case-sensitively look up
	 *          directories in addition to files (in which case `$class` is assumed to actually be a
	 *          namespace).
	 * @return string Returns the absolute path to the file containing `$class`, or `null` if the
	 *         file cannot be found.
	 */
	public static function path($class, array $options = array()) {
		$defaults = array('dirs' => false);
		$options += $defaults;
		$class = ltrim($class, '\\');

		if (isset(static::$_cachedPaths[$class]) && !$options['dirs']) {
			return static::$_cachedPaths[$class];
		}
		foreach (static::$_configurations as $name => $config) {
			$params = $options + $config;
			$suffix = $params['suffix'];

			if ($params['prefix'] && strpos($class, $params['prefix']) !== 0) {
				continue;
			}
			if (!empty($params['transform'])) {
				if (is_callable($params['transform'])) {
					return $params['transform']($class, $params);
				}
				list($match, $replace) = $params['transform'];
				return preg_replace($match, $replace, $class);
			}
			$path = str_replace("\\", '/', substr($class, strlen($params['prefix'])));
			$fullPath = "{$params['path']}/{$path}";

			if ($options['dirs']) {
				$list = glob(dirname($fullPath) . '/*');
				$list = array_map(function($i) { return str_replace('\\', '/', $i); }, $list);

				if (in_array($fullPath . $suffix, $list)) {
					return static::$_cachedPaths[$class] = realpath($fullPath . $suffix);
				}
				return is_dir($fullPath) ? realpath($fullPath) : null;
			}
			return static::$_cachedPaths[$class] = realpath($fullPath . $suffix);
		}
	}

	/**
	 * Performs service location for an object of a specific type. If `$name` is a string, finds the
	 * first instance of a class with the given name in any registered library (i.e. apps, plugins
	 * or vendor libraries registered via `Libraries::add()`), based on each library's order of
	 * precedence. For example, this will find the first model called `File` in any plugin or class
	 * library loaded into an application, including the application itself.
	 *
	 * {{{Libraries::locate('models', 'File');}}}
	 *
	 * Order of precedence is usually based on the order in which the library was registered (via
	 * `Libraries::add()`), unless the library was registered with the `'defer'` option set to
	 * `true`. All libraries with the `'defer'` option set will be searched in
	 * registration-order **after** searching all libraries **without** `'defer'` set. This means
	 * that in the above example, if an app and a plugin both have a model named `File`, then the
	 * model from the app will be returned first, assuming the app was registered first (and
	 * assuming the default settings).
	 *
	 * If `$name` is not specified, `locate()` returns an array with all classes of the specified
	 * type which can be found. By default, `locate()` searches all registered libraries.
	 *
	 * {{{Libraries::locate('models');}}}
	 *
	 * For example, the above will return an array of all model classes in all registered plugins
	 * and libraries (including the app itself).
	 *
	 * To learn more about adding and modifying the class paths used with `locate()`, see the
	 * documentation for the `paths()` method.
	 *
	 * @see lithium\core\Libraries::paths()
	 * @see lithium\core\Libraries::add()
	 * @see lithium\core\Libraries::_locateDeferred()
	 * @param string $type The type of class to search for. Typically follows the name of the
	 *               directory in which the class is stored, i.e. `'models'`, `'controllers'` or
	 *               `'adapter'`. Some classes types, such as adapters, will require a greater
	 *               degree of specificity when looking up the desired class. In this case, the dot
	 *               syntax is used, as in this example when looking up cache adapters:
	 *               `'adapter.storage.cache'`, or this example, when looking up authentication
	 *               adapters: `'adapter.security.auth'`.
	 * @param string $name The base name (without namespace) of the class you wish to locate. If
	 *               unspecified, `locate()` will attempt to find all classes of the type specified
	 *               in `$type`. If you only wish to search for classes within a single plugin or
	 *               library, you may use the dot syntax to prefix the class name with the library
	 *               name, i.e. `'app.Post'`, which will only look for a `Post` model within the
	 *               app itself.
	 * @param array $options The options to use when searching and returning class names.
	 *              - `'type'` _string_: Defaults to `'class'`. If set to `'file'`, returns file
	 *                names instead of class names.
	 *              - `'library'` _string_: When specified, only the given library/plugin name will
	 *                be searched.
	 * @return mixed If `$name` is specified, returns the name of the first class found that matches
	 *         `$name` and `$type`, or returns `null` if no matching classes were found in any
	 *         registered library. If `$name` is not specified, returns an array of all classes
	 *         found which match `$type`.
	 */
	public static function locate($type, $name = null, array $options = array()) {
		$defaults = array('type' => 'class');
		$options += $defaults;

		if (is_object($name) || strpos($name, '\\') !== false) {
			return $name;
		}
		$ident = $name ? $type . '.' . $name : $type;

		if (isset(static::$_cachedPaths[$ident])) {
			return static::$_cachedPaths[$ident];
		}
		$params = static::_params($type, $name);
		extract($params);

		if (!isset(static::$_paths[$type])) {
			return null;
		}
		if (!$name) {
			return static::_locateAll($params, $options);
		}
		$paths = (array) static::$_paths[$type];

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
	 * Returns or sets the the class path cache used for mapping class names to file paths, or
	 * locating classes using `Libraries::locate()`.
	 *
	 * @param array $cache An array of keys and values to use when pre-populating the cache. Keys
	 *              are either class names (which match to file paths as values), or dot-separated
	 *              lookup paths used by `locate()` (which matches to either a single class or an
	 *              array of classes). If `false`, the cache is cleared.
	 * @return array Returns an array of cached class lookups, formatted per the description for
	 *         `$cache`.
	 */
	public static function cache($cache = null) {
		if ($cache === false) {
			static::$_cachedPaths = array();
		}
		if (is_array($cache)) {
			static::$_cachedPaths += $cache;
		}
		return static::$_cachedPaths;
	}

	/**
	 * Performs service location lookups by library, based on the library's `'defer'` flag.
	 * Libraries with `'defer'` set to `true` will be searched last when looking up services.
	 *
	 * @see lithium\core\Libraries::$_paths
	 * @see lithium\core\Libraries::locate()
	 * @param boolean $defer A boolean flag indicating which libraries to search, either the ones
	 *        with the `'defer'` flag set, or the ones without.
	 * @param array $paths List of paths to be searched for the given service (class).  These are
	 *        defined in `lithium\core\Libraries::$_paths`, and are organized by class type.
	 * @param array $params The list of insert parameters to be injected into each path format
	 *        string when searching for classes.
	 * @param array $options
	 * @return string Returns a class path as a string if a given class is found, or null if no
	 *         class in any path matching any of the parameters is located.
	 */
	protected static function _locateDeferred($defer, $paths, $params, array $options = array()) {
		if (isset($options['library'])) {
			$libraries = (array) $options['library'];
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

			foreach ($paths as $pathTemplate => $pathOptions) {
				if (is_int($pathTemplate)) {
					$pathTemplate = $pathOptions;
					$pathOptions = array();
				}
				$opts = $options + $pathOptions;

				if (isset($opts['libraries']) && !in_array($library, (array) $opts['libraries'])) {
					unset($opts['libraries']);
					continue;
				}

				$params['library'] = $library;
				$class = str_replace('\\*', '', String::insert($pathTemplate, $params));

				if (file_exists($file = Libraries::path($class, $opts))) {
					return ($options['type'] === 'file') ? $file : $class;
				}
			}
		}
	}

	/**
	 * Locates all possible classes for given params
	 *
	 * @param string $params
	 * @param string $options
	 * @return void
	 */
	protected static function _locateAll(array $params, array $options = array()) {
		$defaults = array(
			'libraries' => null, 'recursive' => true, 'namespaces' => false,
			'filter' => false, 'exclude' => false,
			'format' => function ($file, $config) {
				$trim = array(strlen($config['path']) + 1, strlen($config['suffix']));
				$file = substr($file, $trim[0], -$trim[1]);
				return $config['prefix'] . str_replace('/', '\\', $file);
			}
		);
		$options += $defaults;
		$paths = (array) static::$_paths[$params['type']];
		$libraries = $options['libraries'] ?: array_keys(static::$_configurations);
		$classes = array();

		foreach ($libraries as $library) {
			$config = static::$_configurations[$library];

			foreach ($paths as $template => $tplOpts) {
				if (is_int($template)) {
					$template = $tplOpts;
					$tplOpts = array();
				}
				$opts = $options + $tplOpts;

				if (isset($opts['libraries']) && !in_array($library, (array) $opts['libraries'])) {
					unset($opts['libraries']);
					continue;
				}
				$path  = String::insert($template, $params, array('escape' => '/'));
				$parts = explode('\\', $path);
				$name  = end($parts);

				$pattern = '/(\/\*)|(\/(?:[A-Z][a-z0-9_]*))|({:\w+})/';
				$opts['path'] = preg_replace($pattern, '', str_replace('\\', '/', $path));

				if (is_dir(str_replace('//', '/', "{$config['path']}/{$opts['path']}"))) {
					$classes = array_merge($classes, static::_search($config, $opts, $name));
				}
			}
		}
		return $classes;
	}

	/**
	 * Helper function for returning known paths given a certain type.
	 *
	 * @see lithium\core\Libraries::$_paths
	 * @param string $type Path type (specified in $_paths).
	 * @param string $params Path parameters.
	 * @return string Valid path name.
	 */
	protected static function _locatePath($type, $params) {
		if (!isset(static::$_paths[$type])) {
			return;
		}
		foreach (static::$_paths[$type] as $path) {
			if (is_dir($path = String::insert($path, $params))) {
				return $path;
			}
		}
	}

	/**
	 * Search file system.
	 *
	 * @param string $config
	 * @param string $options
	 * @param string $name
	 * @return array
	 */
	protected static function _search($config, $options, $name = null) {
		$path = rtrim($config['path'] . $options['path'], '/');

		$search = function($path) use ($config, $options) {
			$suffix = $options['namespaces'] ? '' : $config['suffix'];
			$suffix = isset($options['suffix']) ? $options['suffix'] : $suffix;
			return (array) glob($path . '/*' . $suffix);
		};
		$libs = $search($path);

		if ($options['namespaces'] === true) {
			$filter = '/^.+\/[A-Za-z0-9_]+$|^.*' . preg_quote($config['suffix'], '/') . '/';
			$libs = preg_grep($filter, $libs);
		}
		if ($options['recursive']) {
			$dirs = $queue = array_diff((array) glob($path . '/*', GLOB_ONLYDIR), $libs);
			while ($queue) {
				$dir = array_pop($queue);

				if (!is_dir($dir)) {
					continue;
				}
				$libs = array_merge($libs, $search($dir));
				$queue = array_merge(
					$queue, array_diff((array) glob($dir . '/*', GLOB_ONLYDIR), $libs)
				);
			}
		}
		if (is_callable($options['format'])) {
			foreach ($libs as $i => $file) {
				$libs[$i] = $options['format']($file, $config);
			}
			$libs = $name ? preg_grep("/{$name}$/", $libs) : $libs;
		}
		if ($exclude = $options['exclude']) {
			if (is_string($exclude)) {
				$libs = preg_grep($exclude, $libs, PREG_GREP_INVERT);
			} elseif (is_callable($exclude)) {
				$libs = array_values(array_filter($libs, $exclude));
			}
		}
		if ($filter = $options['filter']) {
			if (is_string($filter)) {
				$libs = preg_grep($filter, $libs) ;
			} elseif (is_callable($filter)) {
				$libs = array_filter(array_map($filter, $libs));
			}
		}
		return $libs;
	}

	/**
	 * Get params from type.
	 *
	 * @param string $type
	 * @param string $name default: null
	 * @return array type, namespace, class, name
	 */
	protected static function _params($type, $name = null) {
		$namespace = $class = '*';
		if (strpos($type, '.')) {
			$parts = explode('.', $type);
			$type = array_shift($parts);

			switch (count($parts)) {
				case 1:
					list($class) = $parts;
				break;
				case 2:
					list($namespace, $class) = $parts;
				break;
				default:
					$class = array_pop($parts);
					$namespace = join('\\', $parts);
				break;
			}
		}
		return compact('type', 'namespace', 'class', 'name');
	}
}

if (!defined('LITHIUM_LIBRARY_PATH')) {
	define('LITHIUM_LIBRARY_PATH', dirname(dirname(__DIR__)));
}

if (!defined('LITHIUM_APP_PATH')) {
	define('LITHIUM_APP_PATH', dirname(LITHIUM_LIBRARY_PATH) . '/app');
}

?>