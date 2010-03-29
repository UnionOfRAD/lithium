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
 * correct short URL. This allows you to keep your application's URL structure nicely decoupled
 * from the underlying software design.
 *
 * For more information on parsing and generating URLs, see the `parse()` and `match()` methods.
 */
class Router extends \lithium\core\StaticObject {

	protected static $_configurations = array();

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
	public static function connect($template, $params = array(), array $options = array()) {
		if (!is_object($template)) {
			$params + array('action' => 'index');
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
		if (!$params = static::parse($request)) {
			return $request;
		}
		$persist = (is_array($params) && isset($params['persist'])) ? $params['persist'] : array();
		unset($params['persist']);

		$request->params = $params;
		$request->persist = $persist;
		return $request;
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
	 * against a connected `Route` object.
	 *
	 * @param array $options
	 * @param object $context
	 * @return string
	 */
	public static function match($options = array(), $context = null) {
		if (is_string($options)) {
			if (is_string($options = static::_matchString($options, $context))) {
				return $options;
			}
		}
		$defaults = array('action' => 'index');

		if ($context && isset($context->persist)) {
			foreach ($context->persist as $key) {
				$defaults[$key] = $context->params[$key];
			}
		}
		$options += $defaults;
		$base = isset($context) ? $context->env('base') : '';

		foreach (static::$_configurations as $route) {
			if ($match = $route->match($options, $context)) {
				return "{$base}{$match}";
			}
		}
	}

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

	protected static function _matchString($path, $context) {
		if (strpos($path, '#') === 0 || strpos($path, 'mailto') === 0 || strpos($path, '://')) {
			return $path;
		}
		if (!preg_match('/^[A-Za-z0-9_]+::[A-Za-z0-9_]+$/', $path)) {
			$base = $context ? $context->env('base') : '';
			$path = trim($path, '/');
			return "{$base}/{$path}";
		}
		list($controller, $action) = explode('::', $path, 2);
		$controller = Inflector::underscore($controller);
		return compact('controller', 'action');
	}
}

?>