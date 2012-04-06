<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\http;

use lithium\util\Inflector;
use lithium\net\http\RoutingException;
use lithium\core\Configurable;

/**
 * The two primary responsibilities of the `Router` class are to generate URLs from parameter lists,
 * and to determine the correct set of dispatch parameters for incoming requests.
 *
 * Using `Route` objects, these two operations can be handled in a reciprocally consistent way.
 * For example, if you wanted the `/login` URL to be routed to
 * `myapp\controllers\SessionsController::add()`, you could set up a route like the following in
 * `config/routes.php`:
 *
 * {{{
 * use lithium\net\http\Router;
 *
 * Router::connect('/login', array('controller' => 'sessions', 'action' => 'add'));
 *
 * // -- or --
 *
 * Router::connect('/login', 'Sessions::add');
 * }}}
 *
 * Not only would that correctly route all requests for `/login` to `SessionsController::add()`, but
 * any time the framework generated a route with matching parameters, `Router` would return the
 * correct short URL.
 *
 * While most framework components that work with URLs (and utilize routing) handle calling the
 * `Router` directly (i.e. controllers doing redirects, or helpers generating links), if you have a
 * scenario where you need to call the `Router` directly, you can use the `match()` method.
 *
 * This allows you to keep your application's URL structure nicely decoupled from the underlying
 * software design. For more information on parsing and generating URLs, see the `parse()` and
 * `match()` methods.
 */
class Router extends \lithium\core\StaticObject {

	/**
	 * Contain the configuration of locations.
	 *
	 * @var array of locations
	 */
	protected static $_locations = null;

	/**
	 * Stores the name of the location in use.
	 * If set to `false`, no location is used.
	 * saved
	 * @see lithium\net\http\Router::location()
	 * @see lithium\net\http\Router::setLocation()
	 * @var string
	 */
	protected static $_location = false;

	/**
	 * Contain a temporary location for the begin/end location feature
	 *
	 * @see lithium\net\http\Router::beginLocation()
	 * @see lithium\net\http\Router::endLocation()
	 * @var string
	 */
	protected static $_locationRestore = false;

	/**
	 * An array of loaded `lithium\net\http\Route` objects used to match Request objects against.
	 *
	 * @var array
	 */
	protected static $_configurations = array();

	/**
	 * Classes used by `Router`.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'route' => 'lithium\net\http\Route'
	);

	public static function config($config = array()) {
		if (!$config) {
			return array('classes' => static::$_classes);
		}
		if (isset($config['classes'])) {
			static::$_classes = $config['classes'] + static::$_classes;
		}
	}

	/**
	 * Connects a new route and returns the current routes array. This method creates a new
	 * `Route` object and registers it with the `Router`. The order in which routes are connected
	 * matters, since the order of precedence is taken into account in parsing and matching
	 * operations.
	 *
	 * @see lithium\net\http\Route
	 * @see lithium\net\http\Router::parse()
	 * @see lithium\net\http\Router::match()
	 * @param string $template An empty string, or a route string "/"
	 * @param array $params An array describing the default or required elements of the route
	 * @param array $options
	 * @return array Array of routes
	 */
	public static function connect($template, $params = array(), $options = array()) {
		if (!is_object($template)) {
			if (is_string($params)) {
				$params = static::_parseString($params, false);
			}
			if (isset($params[0]) && is_array($tmp = static::_parseString($params[0], false))) {
				unset($params[0]);
				$params = $tmp + $params;
			}
			if (is_callable($options)) {
				$options = array('handler' => $options);
			}
			$class = static::$_classes['route'];
			$template = new $class(compact('template', 'params') + $options);
		}
		$name = static::$_location;
		return (static::$_configurations[$name][] = $template);
	}

	/**
	 * Wrapper method which takes a `Request` object, parses it through all attached `Route`
	 * objects, and assigns the resulting parameters to the `Request` object, and returning it.
	 *
	 * @param object $request A request object, usually an instance of `lithium\action\Request`.
	 * @return object Returns a copy of the `Request` object with parameters applied.
	 */
	public static function process($request) {
		if (!$result = static::parse($request)) {
			return $request;
		}
		return $result;
	}

	/**
	 * Accepts an instance of `lithium\action\Request` (or a subclass) and matches it against each
	 * route, in the order that the routes are connected.
	 *
	 * If a route match the request, `lithium\net\http\Router::_location` will be updated according 
	 * the location membership of the route
	 *
	 * @see lithium\action\Request
	 * @see lithium\net\http\Router::connect()
	 * @see lithium\net\http\Location
	 * @param object $request A request object containing URL and environment data.
	 * @return array Returns an array of parameters specifying how the given request should be
	 *         routed. The keys returned depend on the `Route` object that was matched, but
	 *         typically include `'controller'` and `'action'` keys.
	 */
	public static function parse($request) {
		$configs = static::location();
		$locations = array(false);
		$configs += array_fill_keys(array_keys(static::$_configurations), array('absolute' => false));
		foreach($configs as $key => $config){
			if($config['absolute'] && static::matchLocation($key, $request)){
				return static::_parse($request, array($key));
			}
			$locations[] = $key;
		}
		return static::_parse($request, $locations);
	}

	protected static function _parse($request, array $locations) {
		foreach($locations as $name){
			$orig = $request->params;
			$url  = $request->url;
			if(isset(static::$_configurations[$name])){
				foreach (static::$_configurations[$name] as $route) {
					if (!$match = $route->parse($request, compact('url'))) {
						continue;
					}
					$request = $match;

					if ($route->canContinue() && isset($request->params['args'])) {
						$url = '/' . join('/', $request->params['args']);
						unset($request->params['args']);
						continue;
					}
					static::setLocation($name, isset($request->params) ? $request->params : array());
					return $request;
				}
			}
			$request->params = $orig;
		}
	}

	/**
	 * Attempts to match an array of route parameters (i.e. `'controller'`, `'action'`, etc.)
	 * against a connected `Route` object. For example, given the following route:
	 *
	 * {{{
	 * Router::connect('/login', array('controller' => 'users', 'action' => 'login'));
	 * }}}
	 *
	 * This will match:
	 * {{{
	 * $url = Router::match(array('controller' => 'users', 'action' => 'login'));
	 * // returns /login
	 * }}}
	 *
	 * For URLs templates with no insert parameters (i.e. elements like `{:id}` that are replaced
	 * with a value), all parameters must match exactly as they appear in the route parameters.
	 *
	 * Alternatively to using a full array, you can specify routes using a more compact syntax. The
	 * above example can be written as:
	 *
	 * {{{ $url = Router::match('Users::login'); // still returns /login }}}
	 *
	 * You can combine this with more complicated routes; for example:
	 * {{{
	 * Router::connect('/posts/{:id:\d+}', array('controller' => 'posts', 'action' => 'view'));
	 * }}}
	 *
	 * This will match:
	 * {{{
	 * $url = Router::match(array('controller' => 'posts', 'action' => 'view', 'id' => '1138'));
	 * // returns /posts/1138
	 * }}}
	 *
	 * Again, you can specify the same URL with a more compact syntax, as in the following:
	 * {{{
	 * $url = Router::match(array('Posts::view', 'id' => '1138'));
	 * // again, returns /posts/1138
	 * }}}
	 *
	 * You can use either syntax anywhere a URL is accepted, i.e.
	 * `lithium\action\Controller::redirect()`, or `lithium\template\helper\Html::link()`.
	 *
	 * @param string|array $url Options to match to a URL. Optionally, this can be a string
	 *              containing a manually generated URL.
	 * @param object $context An instance of `lithium\action\Request`. This supplies the context for
	 *               any persistent parameters, as well as the base URL for the application.
	 * @param array $options Options for the generation of the matched URL. Currently accepted
	 *              values are:
	 *              - `'absolute'` _boolean_: Indicates whether or not the returned URL should be an
	 *                absolute path (i.e. including scheme and host name).
	 *              - `'host'` _string_: If `'absolute'` is `true`, sets the host name to be used,
	 *                or overrides the one provided in `$context`.
	 *              - `'scheme'` _string_: If `'absolute'` is `true`, sets the URL scheme to be
	 *                used, or overrides the one provided in `$context`.
	 *              - `'base'` _string_: If `'absolute'` is `true`, sets the URL scheme to be
	 *                used, or overrides the one provided in `$context`.
	 *              - `'location'` _string|boolean_: The location name or false if not used.
	 * @return string Returns a generated URL, based on the URL template of the matched route, and
	 *         prefixed with the base URL of the application.
	 */
	public static function match($url = array(), $context = null, array $options = array()) {
		$defaults = array(
			'scheme' => null,
			'host' => null,
			'absolute' => false, 
			'base' => ''
		);

		$base = $context ? rtrim($context->env('base'), '/') : '';
		if ($context) {
			//$scheme = $context->scheme ?: ($context->env('HTTPS') ? 'https://' : 'http://');
			//$host = $context->host ?: $context->env('HTTP_HOST');
			$defaults['host'] = $context->env('HTTP_HOST');
			$defaults['scheme'] = $context->env('HTTPS') ? 'https://' : 'http://';
		}

		$options += array('location' => static::getLocation());
		$vars = array();
		$name = $options['location'];
		if(is_array($name)){
			list($tmp, $vars) = each($name);
			if(!is_array($vars)){
				$vars = $name;
				$name = static::getLocation();
			}
			else{
				$name = $tmp;
			}
		}
		if($name && $config = static::location($name, null, $vars)) {
			$config['host'] = $config['host'] ?: $defaults['host'];
			$config['scheme'] = $config['scheme'] ?: $defaults['scheme'];
			$defaults = array_merge($defaults, $config);
		}

		$defaults += array('base' => isset($defaults['base']) ? $defaults['base'] : '');

		if (substr($defaults['base'], 0, 1) != '/') {
			$defaults['base'] = $base . ($defaults['base'] ? '/' . $defaults['base'] : '');
		}
		else{
			$defaults['base'] = $defaults['base'] ? rtrim($defaults['base'], '/') : $base;
		}

		$options += $defaults;

		if (is_string($url = static::_prepareParams($url, $context, $options))) {
			return $url;
		}

		$base = $options['base'];

		$defaults = array('action' => 'index');
		$url += $defaults;
		$stack = array();

		//$base = rtrim($options['base'], '/');

		$suffix = isset($url['#']) ? "#{$url['#']}" : null;
		unset($url['#']);

		if(isset(static::$_configurations[$name])){
			foreach (static::$_configurations[$name] as $route) {
				if (!$match = $route->match($url, $context)) {
					continue;
				}
				if ($route->canContinue()) {
					$stack[] = $match;
					$export = $route->export();
					$keys = $export['match'] + $export['keys'] + $export['defaults'];
					unset($keys['args']);
					$url = array_diff_key($url, $keys);
					continue;
				}
				if ($stack) {
					$stack[] = $match;
					$match = static::_compileStack($stack);
				}
				$path = rtrim("{$base}{$match}{$suffix}", '/') ?: '/';
				$path = ($options) ? static::_prefix($path, $options) : $path;
				return $path ?: '/';
			}
		}
		$url = static::_formatError($url);
		throw new RoutingException("No parameter match found for URL `{$url}`.");
	}

	protected static function _compileStack($stack) {
		$result = null;

		foreach (array_reverse($stack) as $fragment) {
			if ($result) {
				$result = str_replace('{:args}', ltrim($result, '/'), $fragment);
				continue;
			}
			$result = $fragment;
		}
		return $result;
	}

	protected static function _formatError($url) {
		$match = array("\n", 'array (', ',)', '=> NULL', '(  \'', ',  ');
		$replace = array('', '(', ')', '=> null', '(\'', ', ');
		return str_replace($match, $replace, var_export($url, true));
	}

	protected static function _prepareParams($url, $context = null, array $options) {
		if (is_string($url)) {
			if (strpos($url, '://')) {
				return $url;
			}
			foreach (array('#', '//', 'mailto') as $prefix) {
				if (strpos($url, $prefix) === 0) {
					return $url;
				}
			}
			if (is_string($url = static::_parseString($url, $options))) {
				return static::_prefix($url, $options);
			}
		}
		if (isset($url[0]) && is_array($params = static::_parseString($url[0], $options))) {
			unset($url[0]);
			$url = $params + $url;
		}
		return static::_persist($url, $context);
	}

	/**
	 * Returns the prefix (scheme + hostname) for a URL based on the passed `$options` and the
	 * `$context`.
	 *
	 * @param string $path The URL to be prefixed.
	 * @param object $context The request context.
	 * @param array $options Options for generating the proper prefix. Currently accepted values
	 *              are: `'absolute' => true|false`, `'host' => string` and `'scheme' => string`.
	 * @return string The prefixed URL, depending on the passed options.
	 */
	protected static function _prefix($path, array $options = array()) {
		$defaults = array('scheme' => null, 'host' => null, 'absolute' => false);
		$options += $defaults;
		return ($options['absolute']) ? "{$options['scheme']}{$options['host']}{$path}" : $path;
	}

	/**
	 * Copies persistent parameters (parameters in the request which have been designated to
	 * persist) to the current URL, unless the parameter has been explicitly disabled from
	 * persisting by setting the value in the URL to `null`, or by assigning some other value.
	 *
	 * For example:
	 *
	 * {{{ embed:lithium\tests\cases\net\http\RouterTest::testParameterPersistence(1-10) }}}
	 *
	 * @see lithium\action\Request::$persist
	 * @param array $url The parameters that define the URL to be matched.
	 * @param object $context Typically an instance of `lithium\action\Request`, which contains a
	 *               `$persist` property, which is an array of keys to be persisted in URLs between
	 *                requests.
	 * @return array Returns the modified URL array.
	 */
	protected static function _persist($url, $context) {
		if (!$context || !isset($context->persist)) {
			return $url;
		}
		foreach ($context->persist as $key) {
			$url += array($key => $context->params[$key]);

			if ($url[$key] === null) {
				unset($url[$key]);
			}
		}
		return $url;
	}

	/**
	 * Returns a route from the loaded configurations, by name.
	 *
	 *
	 *
	 * @param string $route The name of the route to request.
	 * @param string $location The name of the location to get routes from. If `null`
	 *                         `lithium\net\http\Router::$_location` will be used
	 *
	 * @return mixed if $route is a named route, return the `lithium\net\http\Route`
	 *               instance or null if not found
	 *               if $route === true, return an array of all `lithium\net\http\Route
	 *               instances for the specified location
	 *               if $route === null, will return all the routes
	 *               for all locations.
	 */
	public static function get($route = null, $location = null) {
		if ($route === null) {
			$result = array();
			$locations = array_keys(static::$_configurations);
			foreach ($locations as $location) {
				$result = array_merge($result, static::$_configurations[$location]);
			}
			return $result;
		}

		if ($location === null) {
			$location = static::$_location;
		}

		if ($route === true) {
			if (isset(static::$_configurations[$location])) {
				return static::$_configurations[$location];
			}
			return array();
		}
		return isset(static::$_configurations[$location][$route]) ? static::$_configurations[$location][$route] : null;
	}

	/**
	 * Resets the `Router` to its default state, unloading all routes.
	 *
	 * @return void
	 */
	public static function reset() {
		static::$_configurations = array();
		static::$_location = false;
		static::$_locationRestore = false;
		if (isset(static::$_locations)) {
			static::$_locations->reset();
		}
	}

	/**
	 * Helper function for taking a path string and parsing it into a controller and action array.
	 *
	 * @param string $path Path string to parse.
	 * @param boolean $context
	 * @return array
	 */
	protected static function _parseString($path, $options) {
		if (!preg_match('/^[A-Za-z0-9_]+::[A-Za-z0-9_]+$/', $path)) {
			$base = rtrim($options['base'], '/');
			$path = trim($path, '/');
			return "{$base}/{$path}";
		}
		list($controller, $action) = explode('::', $path, 2);
		$controller = Inflector::underscore($controller);
		return compact('controller', 'action');
	}

	/**
	 * Setter/getter for router location configuration
	 *
	 * The Setter part:
	 *
	 * Set example 1: simple set location
	 * {{{
	 * Router::location('app', array(
	 *     'absolute' => true,
	 *     'host' => 'localhost',
	 *     'scheme' => 'http://',
	 *     'base' => '/web/tests'
	 * ));
	 * }}}
	 *
	 * Set example 2: set location with variables
	 * {{{
	 * Router::location('app', array(
	 *     'absolute' => true,
	 *     'host' => '{:subdomain:[a-z]+}.{:hostname}.{:tld}',
	 *     'scheme' => '{:scheme:https://}',
	 *     'base' => ''
	 * ));
	 * }}}
	 *
	 * Get example 1: getting the app location
	 * {{{
	 * Router::location('app');
	 * }}}
	 *
	 * The Getter part :
	 *
	 * Example for location with variables :
	 * {{{
	 * Router::location('app', array(
	 *     'absolute' => true,
	 *     'host' => '{:subdomain:[a-z]+}.{:hostname}.{:tld}',
	 *     'scheme' => 'http://',
	 *     'base' => ''
	 * ));
	 * }}}
	 *
	 * NOTICE : The following shouldn't be used directly
	 * To get the parsed location with some variables, use it as following : 
	 * {{{
	 * Router::location('app', null, array(
	 *            'hostname' = 'mysite',
	 *            'sudomain' = 'blog',
	 *            'tld' = 'co.uk',
	 * ));
	 * }}}
	 *
	 * Will return the following array :
	 * 
	 * array(
	 *     'absolute' => true,
	 *     'host' => 'blog.mysite.co.uk',
	 *     'scheme' => 'http://',
	 *     'base' => ''
	 * ));
	 *
	 * @param string $name The name by which this block is referenced.
	 * @param array $params The params. this setter parmas currently accepted
	 *              values are:
	 *              - `'base'` _string_: overrides the one provided in `$context`.
	 *              - `'absolute'` _boolean_: Indicates whether or not the returned URL should be an
	 *                absolute path (i.e. including scheme and host name).
	 *              - `'host'` _string_: If `'absolute'` is `true`, sets the host name to be used,
	 *                or overrides the one provided in `$context`.
	 *              - `'scheme'` _string_: If `'absolute'` is `true`, sets the URL scheme to be
	 *                used, or overrides the one provided in `$context`.
	 *              - `'pattern'` _string_: The regular expression used to match the location.
	 *             if $params is equals to false, the location is removed
	 * @param array $vars Some variables for the getter. If empty, location configuration is
	 *                    returned without beeing parsed
	 * @return array Returns a location array.
	 */
	public static function location($name = null, $config = null, array $vars = array()) {
		if (!isset(static::$_locations)) {
			static::$_locations = new Configurable();
			$self = get_called_class();
			static::$_locations->initConfig = function($name, $config) use ($self) {
				$defaults =array(
					'absolute' => false,
					'host' => null,
					'scheme' => null,
					'base' => '',
					'pattern' => '',
					'values' => array()
				);

				$config += $defaults;

				if (!$config['pattern']) {
					$config = $self::compileLocation($config);
				}
				return $config;
			};
		}
		if (is_array($config) || $config === false) {
			return static::$_locations->set($name, $config);
		}
		$config = static::$_locations->get($name);

		if(!$name || (empty($vars) && empty($config['values']))){
			return $config;
		}
		$vars += $config['values'];
		$match = '@\{:([^:}]+):?((?:[^{]+(?:\{[0-9,]+\})?)*?)\}@S';
		$fields = array('scheme', 'host');
		foreach ($fields as $field) {
			if(preg_match_all($match, $config[$field], $m)){
				$tokens = $m[0];
				$names = $m[1];
				$regexs = $m[2];
				foreach($names as $i => $name){ 
					if(isset($vars[$name])){
						if(($regex = $regexs[$i]) && !preg_match("@^{$regex}\$@",$vars[$name])){
							continue;
						}
						$config[$field] = str_replace($tokens[$i], $vars[$name], $config[$field]);
					}
				}
			}			
		}
		return $config;
	}

	/**
	 * Compiles location array into regular expression patterns for matching against request URLs
	 *
	 * @return void
	 */
	public static function compileLocation(array $config) {
		$defaults =array(
			'absolute' => false,
			'host' => null,
			'scheme' => null,
			'base' => '',
			'pattern' => '',
			'params' => array()
		);
		$config += $defaults;

		if (!$config['absolute']) {
			$config['pattern'] = "@^.*\$@";
		} else {
			$fields = array('scheme', 'host');
			foreach ($fields as $field) {
				$pattern[$field] = preg_replace('/(?!\{[^\}]*)\.(?![^\{]*\})/', '\.', $config[$field]);
				$match = '@\{:([^:}]+):?((?:[^{]+(?:\{[0-9,]+\})?)*?)\}@S';
				if(preg_match_all($match, $pattern[$field], $m)){
					$tokens = $m[0];
					$names = $m[1];
					$regexs = $m[2];
					foreach ($names as $i => $name) {
						$regex = $regexs[$i] ?: '.+?';
						$pattern[$field] = str_replace($tokens[$i], "(?P<{$name}>{$regex})", $pattern[$field]);
						$config['params'][] = $name;
					}
				}
			}
			$config['pattern'] = "@^{$pattern['scheme']}{$pattern['host']}\$@";
		}
		return $config;
	}

	/**
	 * Check if a location match a request
	 *
	 * @param string $name The name of an url location
	 * @param string $request The `lithium\action\Request` to match on
	 * @return mixed The url location or parsed url location if $vars is not empty
	 */
	public static function matchLocation($name, $request) {
		//$scheme = $request->scheme ?: ($request->env('HTTPS') ? 'https://' : 'http://');
		//$host = $request->host ?: $request->env('HTTP_HOST');
		$scheme = $request->env('HTTPS') ? 'https://' : 'http://';
		$host = $request->env('HTTP_HOST');

		if($config = static::location($name)){
			if(preg_match($config['pattern'], $scheme . $host, $match)){
				$result = array_intersect_key ($match, array_flip ($config['params']));
				$request->params += $result;
				return $result ?: true;
			}
		}
		return false;
	}

	/**
	 * Temporarily modify the default location. All Router's methods between
	 * `lithium\net\http\router::beginLocation()` and `lithium\net\http\Router::endLocation()`
	 * will connect routes using the specified location.
	 *
	 * Location configuration can be setted using `lithium\net\http\Router::location()`;
	 *
	 * For example:
	 * {{{
	 * Router::beginLocation('app');
	 * Router::connect(...); //use the 'app' location
	 * Router::endLocation();
	 * }}}
	 *
 	 * {{{
	 * Router::beginLocation('tests');
	 * Router::connect(...); //use the 'tests' location
	 * Router::endLocation();
	 * }}}
	 *
	 * {{{
	 * Router::connect(...); //use the default location
	 * }}}
	 * 
	 *
	 * @see lithium\net\http\Router::location()
	 * @param string $location The name by which this block is referenced.
	 */
	public static function beginLocation($location) {
		static::$_locationRestore = static::getLocation();
		static::setLocation($location);
	}

	/**
	 * Restore the default location configuration
	 */
	public static function endLocation() {
		static::setLocation(static::$_locationRestore);
		static::$_locationRestore = null;
	}

	/**
	 * Permanently change the location to use
	 *
	 * @var string $name The name of the location
	 */
	public static function setLocation($name, $params = array()) {
		if($name && $params && $config = static::$_locations->get($name)){
			$config['values'] = $params;
			static::$_locations->set($name, $config);
		}
		static::$_location = $name;
	}

	/**
	 * Returns the name of the current location
	 *
	 * @return string
	 */
	public static function getLocation() {
		return static::$_location;
	}
}

?>
