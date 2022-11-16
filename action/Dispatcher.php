<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\action;

use lithium\util\Text;
use lithium\util\Inflector;
use lithium\core\Libraries;
use lithium\aop\Filters;
use lithium\action\DispatchException;
use lithium\core\ClassNotFoundException;

/**
 * `Dispatcher` is the outermost layer of the framework, responsible for both receiving the initial
 * HTTP request and sending back a response at the end of the request's life cycle.
 *
 * After either receiving or instantiating a `Request` object instance, the `Dispatcher` passes that
 * instance to the `Router`, which produces the parameters necessary to dispatch the request
 * (unless no route matches, in which case an exception is thrown).
 *
 * Using these parameters, the `Dispatcher` loads and instantiates the correct `Controller` object,
 * and passes it the `Request` object instance. The `Controller` returns a `Response` object to the
 * `Dispatcher`, where the headers and content are rendered and sent to the browser.
 *
 * @see lithium\net\http\Router
 * @see lithium\action\Request
 * @see lithium\action\Response
 * @see lithium\action\Controller
 */
class Dispatcher {

	/**
	 * Fully-namespaced router class reference.  Class must implement a `parse()` method,
	 * which must return an array with (at a minimum) 'controller' and 'action' keys.
	 *
	 * @see lithium\net\http\Router::parse()
	 * @var array
	 */
	protected static $_classes = [
		'router' => 'lithium\net\http\Router'
	];

	/**
	 * Contains pre-process format strings for changing Dispatcher's behavior based on `'rules'`.
	 *
	 * Each key in the array represents a 'rule'; if a key that matches the rule is present
	 * (and not empty) in a route, (i.e. the result of `Router::parse()`) then the rule's
	 * value will be applied to the route before it is dispatched. When applying a rule, any
	 * array elements of the flag which are present in the route will be modified using a
	 * `Text::insert()`-formatted string. Alternatively, a callback can be used to do custom
	 * transformations other than the default `Text::insert()`.
	 *
	 * For example, to implement action prefixes (i.e. `admin_index`), set a rule named
	 * `'admin'`, with a value array containing a modifier key for the `action` element of
	 * a route, i.e.: `['action' => 'admin_{:action}']`. Now, if the `'admin'` key is
	 * present and not empty in the parameters returned from routing, the value of `'action'`
	 * will be rewritten per the settings in the rule:
	 * ```
	 * Dispatcher::config([
	 *	'rules' => [
	 *		'admin' => 'admin_{:action}'
	 *	]
	 * ]);
	 * ```
	 *
	 * The following example shows two rules that continuously or independently transform the
	 * action parameter in order to allow any variations i.e. `'admin_index'`, `'api_index'`
	 * and `'admin_api_index'`.
	 * ```
	 * // ...
	 *		'api' => 'api_{:action}',
	 *		'admin' => 'admin_{:action}'
	 * // ...
	 * ```
	 *
	 * Here's another example. To support normalizing actions, set a rule named `'action'` with
	 * a value array containing a callback that uses `Inflector` to camelize the
	 * action:
	 * ```
	 * // ...
	 *		'action' => ['action' => function($params) {
	 *			return Inflector::camelize(strtolower($params['action']), false);
	 *		}]
	 * // ...
	 * ```
	 *
	 * The entires rules can become a callback as well:
	 * ```
	 * Dispatcher::config([
	 *	'rules' => function($params) {
	 *		// ...
	 *	}
	 * ]);
	 * ```
	 *
	 * @see lithium\action\Dispatcher::config()
	 * @see lithium\util\Text::insert()
	 * @see lithium\util\Inflector
	 * @var array
	 */
	protected static $_rules = [];

	/**
	 * Used to set configuration parameters for the `Dispatcher`.
	 *
	 * @see lithium\action\Dispatcher::$_rules
	 * @param array $config Possible key settings are `'classes'` which sets the class dependencies
	 *              for `Dispatcher` (i.e. `'request'` or `'router'`) and `'rules'`, which sets the
	 *              pre-processing rules for routing parameters. For more information on the
	 *              `'rules'` setting, see the `$_rules` property.
	 * @return array If no parameters are passed, returns an associative array with the current
	 *         configuration, otherwise returns `null`.
	 */
	public static function config(array $config = []) {
		if (!$config) {
			return ['rules' => static::$_rules];
		}

		foreach ($config as $key => $val) {
			$key = "_{$key}";
			if (!is_array($val)) {
				static::${$key} = $val;
				continue;
			}
			if (isset(static::${$key})) {
				static::${$key} = $val + static::${$key};
			}
		}
	}

	/**
	 * Dispatches a request based on a request object (an instance or subclass of
	 * `lithium\net\http\Request`).
	 *
	 * @see lithium\action\Request
	 * @see lithium\action\Response
	 * @param object $request An instance of a request object (usually `lithium\action\Request`)
	 *               with HTTP request information.
	 * @param array $options
	 * @return mixed Returns the value returned from the callable object retrieved from
	 *         `Dispatcher::_callable()`, which is either a string or an instance of
	 *         `lithium\action\Response`.
	 * @filter Allows to perform actions very early or late in the request.
	 */
	public static function run($request, array $options = []) {
		$params = compact('request', 'options');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			$router = static::$_classes['router'];

			$request = $params['request'];
			$options = $params['options'];

			if (($result = $router::process($request)) instanceof Response) {
				return $result;
			}
			$params = static::applyRules($result->params);

			if (!$params) {
				throw new DispatchException('Could not route request.');
			}
			$callable = static::_callable($result, $params, $options);
			return static::_call($callable, $result, $params);
		});
	}

	/**
	 * Attempts to apply a set of formatting rules from `$_rules` to a `$params` array, where each
	 * formatting rule is applied if the key of the rule in `$_rules` is present and not empty in
	 * `$params`.  Also performs sanity checking against `$params` to ensure that no value
	 * matching a rule is present unless the rule check passes.
	 *
	 * @param array $params An array of route parameters to which rules will be applied.
	 * @return array Returns the `$params` array with formatting rules applied to array values.
	 */
	public static function applyRules(&$params) {
		$values = [];
		$rules = static::$_rules;

		if (!$params) {
			return false;
		}

		if (isset($params['controller']) && is_string($params['controller'])) {
			$controller = $params['controller'];

			if (strpos($controller, '.') !== false) {
				list($library, $controller) = explode('.', $controller);
				$controller = $library . '.' . Inflector::camelize($controller);
				$params += compact('library');
			} elseif (strpos($controller, '\\') === false) {
				$controller = Inflector::camelize($controller);

				if (isset($params['library'])) {
					$controller = "{$params['library']}.{$controller}";
				}
			}
			$values = compact('controller');
		}
		$values += $params;

		if (is_callable($rules)) {
			$rules = $rules($params);
		}
		foreach ($rules as $rule => $value) {
			if (!isset($values[$rule])) {
				continue;
			}
			foreach ($value as $k => $v) {
				if (is_callable($v)) {
					$values[$k] = $v($values);
					continue;
				}
				$match = preg_replace('/\{:\w+\}/', '@', $v);
				$match = preg_replace('/@/', '.+', preg_quote($match, '/'));

				if (preg_match('/' . $match . '/i', $values[$k])) {
					continue;
				}
				$values[$k] = Text::insert($v, $values);
			}
		}
		return $values;
	}

	/**
	 * Accepts parameters generated by the `Router` class in `Dispatcher::run()`, and produces a
	 * callable controller object. By default, this method uses the `'controller'` path lookup
	 * configuration in `Libraries::locate()` to return a callable object.
	 *
	 * @param object $request The instance of the `Request` class either passed into or generated by
	 *               `Dispatcher::run()`.
	 * @param array $params The parameter array generated by routing the request.
	 * @param array $options Not currently implemented.
	 * @return object Returns a callable object which the request will be routed to.
	 * @filter
	 */
	protected static function _callable($request, $params, $options) {
		$params = compact('request', 'params', 'options');

		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			$options = ['request' => $params['request']] + $params['options'];
			$controller = $params['params']['controller'];

			try {
				return Libraries::instance('controllers', $controller, $options);
			} catch (ClassNotFoundException $e) {
				throw new DispatchException("Controller `{$controller}` not found.", 2001, $e);
			}
		});
	}

	/**
	 * Invokes the callable object returned by `_callable()`, and returns the results, usually a
	 * `Response` object instance.
	 *
	 * @see lithium\action
	 * @param object $callable Typically a closure or instance of `lithium\action\Controller`.
	 * @param object $request An instance of `lithium\action\Request`.
	 * @param array $params An array of parameters to pass to `$callable`, along with `$request`.
	 * @return mixed Returns the return value of `$callable`, usually an instance of
	 *         `lithium\action\Response`.
	 * @throws lithium\action\DispatchException Throws an exception if `$callable` is not a
	 *         `Closure`, or does not declare the PHP magic `__invoke()` method.
	 * @filter
	 */
	protected static function _call($callable, $request, $params) {
		$params = compact('callable', 'request', 'params');
		return Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
			if (is_callable($callable = $params['callable'])) {
				return $callable($params['request'], $params['params']);
			}
			throw new DispatchException('Result not callable.');
		});
	}
}

?>