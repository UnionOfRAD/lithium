<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\http;

use lithium\util\Inflector;
use lithium\net\http\RoutingException;

/**
 * The two primary responsibilities of the `Router` are to determine the correct set of
 * parameters for incoming request (_parsing_) and second to generate URLs from parameters
 * (_matching_). These two operations can be handled in a reciprocally consistent way.
 *
 * To begin using the router, routes must be defined first. A route maps an URL (template)
 * to a set of paremeters and vice versa. The following example maps the `'/login'` URL
 * to `SessionController::add()`.
 *
 * ```
 * Router::connect('/login', array('controller' => 'Sessions', 'action' => 'add'));
 * ```
 *
 * The `Router` plays an important role in the dispatching process. It allows the `Dispatcher`
 * to invoke the correct controller action for a requested URL. During this process the `Router`
 * will _parse_ the URL and respond with a set of dispatch parameters.
 *
 * ```
 * Router::parse('/login'); // returns array('controller' => 'Sessions', 'action' => 'add')
 * ```
 *
 * Another important thing the `Router` is quite often used for, is the so called _reverse
 * routing_. During this process the `Router` will _match_ a set of parameters and return
 * a URL. In contrast to normal, manually created URLs (i.e. `http://li3.me/support`) These
 * URLs are called _routed URLs_ as they have been generated through the `Router`.
 *
 * ```
 * Router::match(('controller' => 'Sessions', 'action' => 'add')); // returns `'/login'`
 * ```
 *
 * Framework components that work with URLs (and utilize routing), also support
 * reverse routing and accept route parameters.
 *
 * ```
 * $this->html->link(array(...));
 * ```
 *
 * But why use reverse routing and parameters instead of URL string at all? Well, as the whole
 * application URL structure is defined in one place (the routes defintion file) it is quite easy to
 * change the URL structure without touching i.e. the templates.
 *
 * @see lithium\net\http\Router::parse()
 * @see lithium\net\http\Router::match()
 */
class Router extends \lithium\core\StaticObject {

	/**
	 * Contain the configuration of scopes.
	 *
	 * @var array of scopes
	 */
	protected static $_scopes = null;

	/**
	 * Stores the name of the scope to use for building urls.
	 * If is set to `true`, the scope of the user's request will be used.
	 * saved
	 *
	 * @see lithium\net\http\Router::scope()
	 * @var string
	 */
	protected static $_scope = false;

	/**
	 * An array of loaded `Route` objects used to match Request objects against.
	 *
	 * @see lithium\net\http\Route
	 * @var array
	 */
	protected static $_configurations = array();

	/**
	 * Array of closures used to format route parameters when parsing URLs.
	 *
	 * @see lithium\net\http\Router::modifiers()
	 * @var array
	 */
	protected static $_modifiers = array();

	/**
	 * An array of named closures matching up to corresponding route parameter values. Used to
	 * format those values.
	 *
	 * @see lithium\net\http\Router::formatters()
	 * @var array
	 */
	protected static $_formatters = array();

	/**
	 * Classes used by `Router`.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'route'         => 'lithium\net\http\Route',
		'configuration' => 'lithium\core\Configuration'
	);

	/**
	 * Flag for generating Unicode-capable routes. Turn this off if you don't need it, or if you're
	 * using a broken OS distribution (i.e. CentOS).
	 */
	protected static $_unicode = true;

	/**
	 * Modify `Router` configuration settings and dependencies.
	 *
	 * @param array $config Optional array to override configuration. Acceptable keys are
	 *        `'classes'` and `'unicode'`.
	 * @return array Returns the current configuration settings.
	 */
	public static function config($config = array()) {
		if (!$config) {
			return array('classes' => static::$_classes, 'unicode' => static::$_unicode);
		}
		if (isset($config['classes'])) {
			static::$_classes = $config['classes'] + static::$_classes;
		}
		if (isset($config['unicode'])) {
			static::$_unicode = $config['unicode'];
		}
	}

	/**
	 * Connects a new route and returns the current routes array. This method creates a new
	 * `Route` object and registers it with the `Router`. The order in which routes are connected
	 * matters, since the order of precedence is taken into account in parsing and matching
	 * operations.
	 *
	 * A callable can be passed in place of `$options`. In this case the callable acts as a *route
	 * handler*. Route handlers should return an instance of `lithium\net\http\Response`
	 * and can be used to short-circuit the framework's lookup and invocation of controller
	 * actions:
	 * ```
	 * Router::connect('/photos/{:id:[0-9]+}.jpg', array(), function($request) {
	 *     return new Response(array(
	 *         'headers' => array('Content-type' => 'image/jpeg'),
	 *         'body' => Photos::first($request->id)->bytes()
	 *     ));
	 * });
	 * ```
	 *
	 * @see lithium\net\http\Route
	 * @see lithium\net\http\Route::$_handler
	 * @see lithium\net\http\Router::parse()
	 * @see lithium\net\http\Router::match()
	 * @see lithium\net\http\Router::_parseString()
	 * @see lithium\net\http\Response
	 * @param string|object $template An empty string, a route string `/` or an
	 *                      instance of `lithium\net\http\Route`.
	 * @param array|string $params An array describing the default or required elements of
	 *                     the route or alternatively a path string i.e. `Posts::index`.
	 * @param array|callable $options Either an array of options (`'handler'`, `'formatters'`,
	 *                      `'modifiers'`, `'unicode'` as well as any options for `Route`) or
	 *                      a callable that will be used as a route handler.
	 * @return array Array of routes
	 */
	public static function connect($template, $params = array(), $options = array()) {
		if (is_array($options) && isset($options['scope'])) {
			$name = $options['scope'];
		} else {
			$name = static::$_scope;
		}
		if (is_object($template)) {
			return (static::$_configurations[$name][] = $template);
		}
		if (is_string($params)) {
			$params = static::_parseString($params, false);
		}
		if (isset($params[0]) && is_array($tmp = static::_parseString($params[0], false))) {
			unset($params[0]);
			$params = $tmp + $params;
		}
		$params = static::_parseController($params);
		if (is_callable($options)) {
			$options = array('handler' => $options);
		}
		$config = compact('template', 'params') + $options + array(
			'formatters' => static::formatters(),
			'modifiers' => static::modifiers(),
			'unicode' => static::$_unicode
		);
		return (static::$_configurations[$name][] = static::_instance('route', $config));
	}

	/**
	 * Wrapper method which takes a `Request` object, parses it through all attached `Route`
	 * objects, assigns the resulting parameters to the `Request` object, and returns it.
	 *
	 * @param \lithium\action\Request $request
	 * @return \lithium\action\Request Returns a copy of the request with parameters applied.
	 */
	public static function process($request) {
		if (!$result = static::parse($request)) {
			return $request;
		}
		return $result;
	}

	/**
	 * Used to get or set an array of named formatter closures, which are used to format route
	 * parameters when parsing URLs. For example, the following would match a `posts/index` url
	 * to a `PostsController::indexAction()` method.
	 *
	 * ```
	 * use lithium\util\Inflector;
	 *
	 * Router::modifiers(array(
	 *     'controller' => function($value) {
	 *         return Inflector::camelize($value);
	 *     },
	 *     'action' => function($value) {
	 *         return Inflector::camelize($value) . 'Action';
	 *     }
	 * ));
	 * ```
	 *
	 * _Note_: Because modifiers are copied to `Route` objects on an individual basis, make sure
	 * you append your custom modifiers _before_ connecting new routes.
	 *
	 * @param array $modifiers An array of named formatter closures to append to (or overwrite) the
	 *        existing list.
	 * @return array Returns the formatters array.
	 */
	public static function modifiers(array $modifiers = array()) {
		if (!static::$_modifiers) {
			static::$_modifiers = array(
				'args' => function($value) {
					return explode('/', $value);
				},
				'controller' => function($value) {
					return Inflector::camelize($value);
				}
			);
		}
		if ($modifiers) {
			static::$_modifiers = array_filter($modifiers + static::$_modifiers);
		}
		return static::$_modifiers;
	}

	/**
	 * Used to get or set an array of named formatter closures, which are used to format route
	 * parameters when generating URLs. For example, for controller/action parameters to be dashed
	 * instead of underscored or camelBacked, you could do the following:
	 *
	 * ```
	 * use lithium\util\Inflector;
	 *
	 * Router::formatters(array(
	 *     'controller' => function($value) { return Inflector::slug($value); },
	 *     'action' => function($value) { return Inflector::slug($value); }
	 * ));
	 * ```
	 *
	 * _Note_: Because formatters are copied to `Route` objects on an individual basis, make sure
	 * you append custom formatters _before_ connecting new routes.
	 *
	 * @param array $formatters An array of named formatter closures to append to (or overwrite) the
	 *        existing list.
	 * @return array Returns the formatters array.
	 */
	public static function formatters(array $formatters = array()) {
		if (!static::$_formatters) {
			static::$_formatters = array(
				'args' => function($value) {
					return is_array($value) ? join('/', $value) : $value;
				},
				'controller' => function($value) {
					if (strpos($value, '\\')) {
						$value = explode('\\', $value);
						$value = end($value);
					}
					return Inflector::underscore($value);
				}
			);
		}
		if ($formatters) {
			static::$_formatters = array_filter($formatters + static::$_formatters);
		}
		return static::$_formatters;
	}

	/**
	 * Accepts an instance of `lithium\action\Request` (or a subclass) and matches it against each
	 * route, in the order that the routes are connected.
	 *
	 * If a route match the request, `lithium\net\http\Router::_scope` will be updated according
	 * the scope membership of the route
	 *
	 * @see lithium\action\Request
	 * @see lithium\net\http\Router::connect()
	 * @param object $request A request object containing URL and environment data.
	 * @return array Returns an array of parameters specifying how the given request should be
	 *         routed. The keys returned depend on the `Route` object that was matched, but
	 *         typically include `'controller'` and `'action'` keys.
	 */
	public static function parse($request) {
		foreach (static::$_configurations as $name => $value) {
			$original = $request->params;
			$name = is_int($name) ? false : $name;

			if (!$url = static::_parseScope($name, $request)) {
				continue;
			}

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

				static::attach($name, null, isset($request->params) ? $request->params : array());
				static::scope($name);

				return $request;
			}
			$request->params = $original;
		}
	}

	/**
	 * Attempts to match an array of route parameters (i.e. `'controller'`, `'action'`, etc.)
	 * against a connected `Route` object. For example, given the following route:
	 *
	 * ```
	 * Router::connect('/login', array('controller' => 'users', 'action' => 'login'));
	 * ```
	 *
	 * This will match:
	 * ```
	 * $url = Router::match(array('controller' => 'users', 'action' => 'login'));
	 * // returns /login
	 * ```
	 *
	 * For URLs templates with no insert parameters (i.e. elements like `{:id}` that are replaced
	 * with a value), all parameters must match exactly as they appear in the route parameters.
	 *
	 * Alternatively to using a full array, you can specify routes using a more compact syntax. The
	 * above example can be written as:
	 * ```
	 * $url = Router::match('Users::login'); // still returns /login
	 * ```
	 *
	 * You can combine this with more complicated routes; for example:
	 * ```
	 * Router::connect('/posts/{:id:\d+}', array('controller' => 'posts', 'action' => 'view'));
	 * ```
	 *
	 * This will match:
	 * ```
	 * $url = Router::match(array('controller' => 'posts', 'action' => 'view', 'id' => '1138'));
	 * // returns /posts/1138
	 * ```
	 *
	 * Again, you can specify the same URL with a more compact syntax, as in the following:
	 * ```
	 * $url = Router::match(array('Posts::view', 'id' => '1138'));
	 * // again, returns /posts/1138
	 * ```
	 *
	 * You can use either syntax anywhere an URL is accepted, i.e. when redirecting
	 * or creating links using the `Html` helper.
	 *
	 * @see lithium\action\Controller::redirect()
	 * @see lithium\template\helper\Html::link()
	 * @param array|string $url An array of parameters to match, or paremeters in their
	 *        shorthand form (i.e. `'Posts::view'`). Also takes non-routed, manually generated
	 *        URL strings.
	 * @param \lithium\action\Request $context This supplies the context for
	 *        any persistent parameters, as well as the base URL for the application.
	 * @param array $options Options for the generation of the matched URL. Currently accepted
	 *        values are:
	 *        - `'absolute'` _boolean_: Indicates whether or not the returned URL should be an
	 *          absolute path (i.e. including scheme and host name).
	 *        - `'host'` _string_: If `'absolute'` is `true`, sets the host name to be used,
	 *          or overrides the one provided in `$context`.
	 *        - `'scheme'` _string_: If `'absolute'` is `true`, sets the URL scheme to be
	 *          used, or overrides the one provided in `$context`.
	 *        - `'scope'` _string_: Optionnal scope name.
	 * @return string Returns a generated URL, based on the URL template of the matched route, and
	 *         prefixed with the base URL of the application.
	 */
	public static function match($url = array(), $context = null, array $options = array()) {
		$options = static::_matchOptions($url, $context, $options);

		if (is_string($url = static::_prepareParams($url, $context, $options))) {
			return $url;
		}

		$base = $options['base'];
		$url += array('action' => 'index');
		$stack = array();

		$suffix = isset($url['#']) ? "#{$url['#']}" : null;
		unset($url['#']);

		$scope = $options['scope'];
		if (isset(static::$_configurations[$scope])) {
			foreach (static::$_configurations[$scope] as $route) {
				if (!$match = $route->match($url + array('scope' => static::attached($scope)), $context)) {
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
				$path = ($options) ? static::_prefix($path, $context, $options) : $path;
				return $path ?: '/';
			}
		}
		$url = static::_formatError($url);
		$message = "No parameter match found for URL `{$url}`";
		$message .= $scope ? " in `{$scope}` scope." : '.';
		throw new RoutingException($message);
	}

	/**
	 * Initialize options for `Router::match()`.
	 *
	 * @param string|array $url Options to match to a URL. Optionally, this can be a string
	 *        containing a manually generated URL.
	 * @param \lithium\action\Request $context
	 * @param array $options Options for the generation of the matched URL.
	 * @return array The initialized options.
	 */
	protected static function _matchOptions(&$url, $context, $options) {
		$defaults = array(
			'scheme' => null,
			'host' => null,
			'absolute' => false,
			'base' => ''
		);
		if ($context) {
			$defaults = array(
				'base' => $context->env('base'),
				'host' => $context->host,
				'scheme' => $context->scheme . ($context->scheme ? '://' : '//')
			) + $defaults;
		}

		$options += array('scope' => static::scope());
		$vars = array();
		$scope = $options['scope'];
		if (is_array($scope)) {
			list($tmp, $vars) = each($scope);
			if (!is_array($vars)) {
				$vars = $scope;
				$scope = static::scope();
			} else {
				$scope = $tmp;
			}
		}
		if ($config = static::attached($scope, $vars)) {
			if (is_array($url)) {
				unset($url['library']);
			}
			$config['host'] = $config['host'] ? : $defaults['host'];
			if ($config['scheme'] === false) {
				$config['scheme'] = '//';
			} else {
				$config['scheme'] .= ($config['scheme'] ? '://' : $defaults['scheme']);
			}
			$config['scheme'] = $config['scheme'] ? : 'http://';

			$base = isset($config['base']) ? '/' . $config['base'] : $defaults['base'];
			$base = $base . ($config['prefix'] ? '/' . $config['prefix'] : '');
			$config['base'] = rtrim($config['absolute'] ? '/' . trim($base, '/') : $base, '/');
			$defaults = $config + $defaults;
		}
		return $options + $defaults;
	}

	protected static function _compileStack($stack) {
		$result = null;
		list($result, $query) = array_pad(explode('?', array_pop($stack), 2), 2, null);
		foreach (array_reverse($stack) as $fragment) {
			$result = ltrim($result, '/');
			$result = str_replace(($result ? '' : '/') . '{:args}', $result, $fragment);
		}
		return $result . ($query ? '?' . $query : '');
	}

	protected static function _formatError($url) {
		$match = array("\n", 'array (', ',)', '=> NULL', '(  \'', ',  ');
		$replace = array('', '(', ')', '=> null', '(\'', ', ');
		return str_replace($match, $replace, var_export($url, true));
	}

	protected static function _parseController(array $params) {
		if (!isset($params['controller'])) {
			return $params;
		}
		if (strpos($params['controller'], '.')) {
			$separated = explode('.', $params['controller'], 2);
			list($params['library'], $params['controller']) = $separated;
		}
		if (strpos($params['controller'], '\\') === false) {
			$params['controller'] = Inflector::camelize($params['controller']);
		}
		return $params;
	}

	/**
	 * Prepares URL parameters for matching. Detects and Passes through un-routed URL strings,
	 * leaving them untouched.
	 *
	 * Will not attempt to parse strings as shorthand parameters but instead interpret
	 * them as normal, non-routed URLs when they are prefixed with a known scheme.
	 *
	 * @param array|string $url An array of parameters, shorthand parameter string or URL string.
	 * @param \lithium\action\Request $context
	 * @param array $options
	 * @return array|string Depending on the type of $url either a string or an array.
	 */
	protected static function _prepareParams($url, $context, array $options) {
		if (is_string($url)) {
			if (strpos($url, '://') !== false) {
				return $url;
			}
			if (preg_match('%^((#|//)|(mailto|tel|sms|javascript):)%', $url)) {
				return $url;
			}
			if (is_string($url = static::_parseString($url, $context, $options))) {
				return static::_prefix($url, $context, $options);
			}
		}
		$isArray = (
			isset($url[0]) &&
			is_array($params = static::_parseString($url[0], $context, $options))
		);
		if ($isArray) {
			unset($url[0]);
			$url = $params + $url;
		}
		return static::_persist(static::_parseController($url), $context);
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
	protected static function _prefix($path, $context = null, array $options = array()) {
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
	 * ``` embed:lithium\tests\cases\net\http\RouterTest::testParameterPersistence(1-10) ```
	 *
	 * @see lithium\action\Request::$persist
	 * @param array $url The parameters that define the URL to be matched.
	 * @param \lithium\action\Request $context A request object, which contains a
	 *        `$persist` property, which is an array of keys to be persisted in URLs between
	 *        requests.
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
	 * Returns one or multiple connected routes.
	 *
	 * A specific route can be retrived by providing its index. All connected routes inside all
	 * scopes may be retrieved by providing `null` instead of the route index. To retrieve all
	 * routes for the current scope only, pass `true` for the `$scope` parameter.
	 *
	 * @param integer $route Index of the route.
	 * @param string|boolean $scope Name of the scope to get routes from. Uses default
	 *        scope if `true`.
	 * @return object|array|null If $route is an integer, returns the route object at given index or
	 *         if that fails returns `null`. If $route is `null` returns an array of routes or
	 *         scopes with their respective routes depending on the value of $scope.
	 */
	public static function get($route = null, $scope = null) {
		if ($route === null && $scope === null) {
			return static::$_configurations;
		}

		if ($scope === true) {
			$scope = static::$_scope;
		}

		if ($route === null && $scope !== null) {
			if (isset(static::$_configurations[$scope])) {
				return static::$_configurations[$scope];
			}
			return array();
		}

		if (!isset(static::$_configurations[$scope][$route])) {
			return null;
		}
		return static::$_configurations[$scope][$route];
	}

	/**
	 * Resets the `Router` to its default state, unloading all routes.
	 */
	public static function reset() {
		static::$_configurations = array();
		static::$_scope = false;
		if (isset(static::$_scopes)) {
			static::$_scopes->reset();
		}
	}

	/**
	 * Helper function for taking a path string and parsing it into a controller and action array.
	 *
	 * @param string $path Path string to parse i.e. `li3_bot.Logs::index` or `Posts::index`.
	 * @param boolean $context
	 * @return array
	 */
	protected static function _parseString($path, $context, array $options = array()) {
		if (!preg_match('/^[A-Za-z0-9._\\\\]+::[A-Za-z0-9_]+$/', $path)) {
			$base = rtrim($options['base'], '/');
			if ((!$path || $path[0] != '/') && $context && isset($context->controller)) {
				$formatters = static::formatters();
				$base .= '/' . $formatters['controller']($context->controller);
			}
			$path = trim($path, '/');
			return "{$base}/{$path}";
		}
		list($controller, $action) = explode('::', $path, 2);
		return compact('controller', 'action');
	}

	/**
	 * Scope getter/setter.
	 *
	 * Special use case: If `$closure` is not null executing the closure inside
	 * the specified scope.
	 *
	 * @param string $name Name of the scope to use.
	 * @param \Closure $closure A closure to execute inside the scope.
	 * @return mixed Returns the previous scope if if `$name` is not null and `$closure` is null,
	 *         returns the default used scope if `$name` is null, otherwise returns `null`.
	 */
	public static function scope($name = null, \Closure $closure = null) {
		if ($name === null) {
			return static::$_scope;
		}

		if ($closure === null) {
			$former = static::$_scope;
			static::$_scope = $name;
			return $former;
		}

		$former = static::$_scope;
		static::$_scope = $name;
		call_user_func($closure);
		static::$_scope = $former;
	}

	/**
	 * Attach a scope to a mount point.
	 *
	 * Example 1:
	 * ```
	 * Router::attach('app', array(
	 *     'absolute' => true,
	 *     'host' => 'localhost',
	 *     'scheme' => 'http://',
	 *     'prefix' => 'web/tests'
	 * ));
	 * ```
	 *
	 * Example 2:
	 * ```
	 * Router::attach('app', array(
	 *     'absolute' => true,
	 *     'host' => '{:subdomain:[a-z]+}.{:hostname}.{:tld}',
	 *     'scheme' => '{:scheme:https://}',
	 *     'prefix' => ''
	 * ));
	 * ```
	 *
	 * Attach the variables to populate for the app scope.
	 * ```
	 * Router::attach('app', null, array(
	 *     'subdomain' => 'www',
	 *     'hostname' => 'li3',
	 *     'tld' => 'me'
	 * ));
	 * ```
	 *
	 * @param string Name of the scope.
	 * @param mixed Settings of the mount point or `null` for setting only variables to populate.
	 * @param array Variables to populate for the scope.
	 */
	public static function attach($name, $config = null, array $vars = array()) {
		if ($name === false) {
			return null;
		}

		if (!isset(static::$_scopes)) {
			static::_initScopes();
		}

		if ($config === null) {
			if ($vars && ($config = static::$_scopes->get($name))) {
				$config['values'] = $vars;
				static::$_scopes->set($name, $config);
			}
			return;
		}

		if (is_array($config) || $config === false) {
			static::$_scopes->set($name, $config);
		}
	}

	/**
	 * Returns an attached mount point configuration.
	 *
	 * Example:
	 * ```
	 * Router::attach('app', array(
	 *     'absolute' => true,
	 *     'host' => '{:subdomain:[a-z]+}.{:hostname}.{:tld}',
	 *     'scheme' => '{:scheme:https://}',
	 *     'prefix' => ''
	 * ));
	 * ```
	 *
	 * ```
	 * $result = Router::attached('app', array(
	 *     'subdomain' => 'app',
	 *     'hostname' => 'blog',
	 *     'tld' => 'co.uk'
	 * ));
	 * ```
	 *
	 * Will give the following array in `$result`:
	 *
	 * array(
	 *     'absolute' => true,
	 *     'host' => 'blog.mysite.co.uk',
	 *     'scheme' => 'http://',
	 *     'prefix' => ''
	 * ));
	 *
	 * @param string Name of the scope.
	 * @param array Optionnal variables which override the default setted variables with
	 *        `lithium\net\http\Router::attach()`for population step.
	 * @return mixed The settings array of the scope or an array of settings array
	 *         if `$name === null`.
	 */
	public static function attached($name = null, array $vars = array()) {
		if ($name === false) {
			return null;
		}

		if (!isset(static::$_scopes)) {
			static::_initScopes();
		}

		if ($name === null) {
			return static::$_scopes->get();
		} elseif (!$config = static::$_scopes->get($name)) {
			static::$_scopes->set($name, array());
			$config = static::$_scopes->get($name);
		}
		$vars += $config['values'];
		$match = '@\{:([^:}]+):?((?:[^{]+(?:\{[0-9,]+\})?)*?)\}@S';
		$fields = array('scheme', 'host');
		foreach ($fields as $field) {
			if (preg_match_all($match, $config[$field], $m)) {
				$tokens = $m[0];
				$names = $m[1];
				$regexs = $m[2];
				foreach ($names as $i => $name) {
					if (isset($vars[$name])) {
						if (($regex = $regexs[$i]) && !preg_match("@^{$regex}\$@", $vars[$name])) {
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
	 * Initialize `static::$_scopes` with a `lithium\core\Configuration` instance.
	 */
	protected static function _initScopes() {
		static::$_scopes = static::_instance('configuration');

		static::$_scopes->initConfig = function($name, $config) {
			$defaults = array(
				'absolute' => false,
				'host' => null,
				'scheme' => null,
				'base' => null,
				'prefix' => '',
				'pattern' => '',
				'values' => array(),
				'library' => $name
			);

			$config += $defaults;

			if (!$config['pattern']) {
				$config = static::_compileScope($config);
			}
			$config['base'] = $config['base'] ? trim($config['base'], '/') : $config['base'];
			return $config;
		};
	}

	/**
	 * Compiles the scope into regular expression patterns for matching against request URLs
	 *
	 * @param array $config Array of settings.
	 * @return array Returns the complied settings.
	 */
	protected static function _compileScope(array $config) {
		$defaults = array(
			'absolute' => false,
			'host' => null,
			'scheme' => null,
			'base' => null,
			'prefix' => '',
			'pattern' => '',
			'params' => array()
		);

		$config += $defaults;

		$config['prefix'] = trim($config['prefix'], '/');
		$prefix = '/' . ($config['prefix'] ? $config['prefix'] . '/' : '');

		if (!$config['absolute']) {
			$config['pattern'] = "@^{$prefix}@";
		} else {
			$fields = array('scheme', 'host');
			foreach ($fields as $field) {
				$dots = '/(?!\{[^\}]*)\.(?![^\{]*\})/';
				$pattern[$field] = preg_replace($dots, '\.', $config[$field]);
				$match = '@\{:([^:}]+):?((?:[^{]+(?:\{[0-9,]+\})?)*?)\}@S';
				if (preg_match_all($match, $pattern[$field], $m)) {
					$tokens = $m[0];
					$names = $m[1];
					$regexs = $m[2];
					foreach ($names as $i => $name) {
						$regex = $regexs[$i] ? : '[^/]+?';
						$pattern[$field] = str_replace(
							$tokens[$i],
							"(?P<{$name}>{$regex})",
							$pattern[$field]
						);
						$config['params'][] = $name;
					}
				}
			}
			$pattern['host'] = $pattern['host'] ? : 'localhost';
			$pattern['scheme'] = $pattern['scheme'] . ($pattern['scheme'] ? '://' : '(.*?)//');
			$config['pattern'] = "@^{$pattern['scheme']}{$pattern['host']}{$prefix}@";
		}
		return $config;
	}

	/**
	 * Return the unscoped url to route.
	 *
	 * @param string $name Scope name.
	 * @param string $request A `lithium\action\Request` instance .
	 * @return mixed The url to route, or `false` if the request doesn't match the scope.
	 */
	protected static function _parseScope($name, $request) {
		$url = trim($request->url, '/');
		$url = $url ? '/' . $url . '/' : '/';

		if (!$config = static::attached($name)) {
			return $url;
		}

		$scheme = $request->scheme . ($request->scheme ? '://' : '//');
		$host = $request->host;

		if ($config['absolute']) {
			preg_match($config['pattern'], $scheme . $host . $url, $match);
		} else {
			preg_match($config['pattern'], $url, $match);
		}

		if ($match) {
			$result = array_intersect_key($match, array_flip($config['params']));
			$request->params = array();
			if (isset($config['library'])) {
				$request->params['library'] = $config['library'];
			}
			$request->params += $result;
			if ($config['prefix']) {
				$url = preg_replace('@^/' . trim($config['prefix'], '/') . '@', '', $url);
			}
			return $url;
		}
		return false;
	}
}

?>