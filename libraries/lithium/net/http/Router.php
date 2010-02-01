<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\http;

use \lithium\util\Collection;

class Router extends \lithium\core\StaticObject {

	protected static $_configuration = null;

	protected static $_classes = array(
		'route' => '\lithium\net\http\Route'
	);

	public static function __init() {
		static::$_configuration = new Collection();
	}

	/**
	 * Connects a new route and returns the current routes array.
	 *
	 * @param string $template An empty string, or a route string "/"
	 * @param array $params An array describing the default or required elements of the route
	 * @param array $options
	 * @return array Array of routes
	 * @see lithium\net\http\Router::parse()
	 */
	public static function connect($template, $params = array(), $options = array()) {
		if ($template === null) {
			return static::__init();
		}

		if (!is_object($template)) {
			$params + array('action' => 'index');
			$class = static::$_classes['route'];
			$template = new $class(compact('template', 'params', 'options'));
		}
		return (static::$_configuration[] = $template);
	}

	/**
	 * Takes an instance of lithium\net\http\Request (or a subclass) and matches it against each
	 * route, in the order that the routes are connected.
	 *
	 * @param object $request A request object containing URL and environment data.
	 * @return array
	 * @see lithium\net\http\Router::connect()
	 */
	public static function parse($request) {
		return static::$_configuration->first(function($route) use ($request) {
			return $route->parse($request);
		});
	}

	/**
	 * Attempts to match an array of route parameters (i.e. `'controller'`, `'action'`, etc.)
	 * against a connected `Route` object.
	 *
	 * @param array $options
	 * @param object $context
	 * @return string
	 * @todo Implement full context support
	 */
	public static function match($options = array(), $context = null) {
		if (is_string($options)) {
			$path = $options;

			if (strpos($path, '#') === 0 || strpos($path, 'mailto') === 0 || strpos($path, '://')) {
				return $path;
			}
			$base = $context ? $context->env('base') : '';
			$path = trim($path, '/');
			return "{$base}/{$path}";
		}
		$defaults = array_filter(array(
			'action' => ($context && $context->action) ? $context->action : 'index',
			'controller' => ($context && $context->action) ? $context->controller : null
		));
		$options += $defaults;
		$base = isset($context) ? $context->env('base') : '';

		return $base . static::$_configuration->first(function($route) use ($options, $context) {
			return $route->match($options, $context);
		});
	}

	public static function get($route = null) {
		if (empty(static::$_configuration)) {
			static::__init();
		}
		if (is_null($route)) {
			return static::$_configuration;
		}
		return isset(static::$_configuration[$route]) ? static::$_configuration[$route] : null;
	}
}

?>