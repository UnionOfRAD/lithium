<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\http;

use \lithium\util\Inflector;
use \lithium\util\Collection;

/**
 * The two primary responsibilities of the `Router` class are to generate URLs from parameter lists,
 * and to determine the correct set of dispatch parameters for incoming requests.
 *
 * Using `Route` objects, these two operations can be handled in a reciprocally consistent way.
 * For example, if you wanted the `/login` URL to be routed to
 * `app\controllers\UsersController::login()`, you could set up a route like the following in
 * `app/config/routes.php`:
 * {{{
 * use \lithium\net\http\Router;
 *
 * Router::connect('/login', array('controller' => 'users', 'action' => 'login'));}}}
 *
 * Not only would that correctly route all requests for `/login` to `UsersController::index()`, but
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
	 * An array of loaded lithium\net\http\Route objects used to match Request objects against.
	 *
	 * @var array
	 */
	protected static $_configurations = array();

	/**
	 * Classes used by `Router`.
	 *
	 * @package default
	 * @author John David Anderson
	 */
	protected static $_classes = array(
		'route' => '\lithium\net\http\Route'
	);

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
			$params += array('action' => 'index');

			if (is_callable($options)) {
				$options = array('handler' => $options);
			}
			$class = static::$_classes['route'];
			$template = new $class(compact('template', 'params') + $options);
		}
		return (static::$_configurations[] = $template);
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
	 * @see lithium\action\Request
	 * @see lithium\net\http\Router::connect()
	 * @param object $request A request object containing URL and environment data.
	 * @return array Returns an array of parameters specifying how the given request should be
	 *         routed. The keys returned depend on the `Route` object that was matched, but
	 *         typically include `'controller'` and `'action'` keys.
	 */
	public static function parse($request) {
		foreach (static::$_configurations as $route) {
			if ($match = $route->parse($request)) {
				return $match;
			}
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
	 * {{{ $url = Router::match('User::login'); // still returns /login }}}
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
	 * @param array $options Array of options to match to a URL. Optionally, this can be a string
	 *              containing a manually generated URL.
	 * @param object $context An instance of `lithium\action\Request`. This supplies the context for
	 *               any persistent parameters, as well as the base URL for the application.
	 * @return string Returns a generated URL, based on the URL template of the matched route, and
	 *         prefixed with the base URL of the application.
	 */
	public static function match($options = array(), $context = null) {
		if (is_string($path = $options)) {
			if (strpos($path, '#') === 0 || strpos($path, 'mailto') === 0 || strpos($path, '://')) {
				return $path;
			}
			if (is_string($options = static::_parseString($options, $context))) {
				return $options;
			}
		}
		if (isset($options[0]) && is_array($params = static::_parseString($options[0], $context))) {
			unset($options[0]);
			$options = $params + $options;
		}

		if ($context && isset($context->persist)) {
			foreach ($context->persist as $key) {
				$options += array($key => $context->params[$key]);
				if ($options[$key] === null) {
					unset($options[$key]);
				}
			}
		}

		$defaults = array('action' => 'index');
		$options += $defaults;
		$base = isset($context) ? $context->env('base') : '';

		foreach (static::$_configurations as $route) {
			if ($match = $route->match($options, $context)) {
				return rtrim("{$base}{$match}", '/');
			}
		}
	}

	/**
	 * Returns a route from the loaded configurations, by name.
	 *
	 * @param string $route Name of the route to request.
	 * @return lithium\net\http\Route 
	 */
	public static function get($route = null) {
		if ($route === null) {
			return static::$_configurations;
		}
		return isset(static::$_configurations[$route]) ? static::$_configurations[$route] : null;
	}

	/**
	 * Resets the `Router` to its default state, unloading all routes.
	 *
	 * @return void
	 */
	public static function reset() {
		static::$_configurations = array();
	}

	/**
	 * Helper function for taking a path string and parsing it into a controller and action array.
	 *
	 * @param string $path Path string to parse.
	 * @param boolean $context 
	 * @return array
	 */
	protected static function _parseString($path, $context) {
		if (!preg_match('/^[A-Za-z0-9_]+::[A-Za-z0-9_]+$/', $path)) {
			$base = $context ? $context->env('base') : '';
			$path = trim($path, '/');
			return $context !== false ? "{$base}/{$path}" : null;
		}
		list($controller, $action) = explode('::', $path, 2);
		$controller = Inflector::underscore($controller);
		return compact('controller', 'action');
	}
}

?>