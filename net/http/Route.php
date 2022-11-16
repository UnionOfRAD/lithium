<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\net\http;

use lithium\core\AutoConfigurable;

/**
 * The `Route` class represents a single URL pattern which is matched against incoming requests, in
 * order to determine the correct controller and action that an HTTP request should be dispatched
 * to.
 *
 * Typically, `Route` objects are created and handled through the `Router` class, as follows.
 * When connecting a route, a `Route` object is instantiated behind the scenes, and added
 * to the `Router`'s collection.
 *
 * ```
 * Router::connect("/{:controller}/{:action}");
 * ```
 *
 * This following matches a set of parameters against all `Route` objects contained in Router, and
 * if a match is found, returns a string URL with parameters inserted into the URL pattern.
 *
 * ```
 * Router::match(["controller" => "users", "action" => "login"]); // returns "/users/login"
 * ```
 *
 * For more advanced routing, however, you can directly instantiate a `Route` object, a subclass,
 * or any class that implements `parse()` and `match()` (see the documentation for each individual
 * method) and configure it manually -- if, for example, you want the route to match different
 * incoming URLs than it generates.
 *
 * ```
 * $route = new Route([
 *        'template' => '/users/{:user}',
 *        'pattern' => '@^/u(?:sers)?(?:/(?P<user>[^\/]+))$@',
 *        'params' => ['controller' => 'users', 'action' => 'index'],
 *        'match' => ['controller' => 'users', 'action' => 'index'],
 *        'defaults' => ['controller' => 'users'],
 *        'keys' => ['user' => 'user'],
 *        'options' => ['compile' => false, 'wrap' => false]
 * ]);
 *
 * Router::connect($route); // this will match '/users/<username>' or '/u/<username>'.
 * ```
 *
 * @see lithium\net\http\Route::compile()
 * @see lithium\net\http\Router
 */
class Route {

	use AutoConfigurable;

	/**
	 * The URL template string that the route matches, i.e.
	 * `'/admin/{:controller}/{:id:\d+}/{:args}'`.
	 *
	 * This string can contain any combination of...
	 *
	 * 1. fixed elements, i.e. `'/admin'`
	 * 2. plain capture elements, i.e. `'/{:controller}'`
	 * 3. capture elements paired with regular expressions, i.e. `'/{:id:\d+}'`
	 * 4. capture elements paired with named regular expression patterns, `'/{:id:ID}'`
	 * 5. the speciall wildcard capture element `'{:args}'`
	 *
	 * @var string
	 */
	protected $_template = '';

	/**
	 * The regular expression used to match URLs.
	 *
	 * This regular expression is typically _compiled_ down from the higher-level syntax used in
	 * `$_template`, but can be set manually with compilation turned off in the constructor for
	 * extra control or if you are using pre-compiled `Route` objects.
	 *
	 * @var string
	 * @see lithium\net\http\Route::$_template
	 * @see lithium\net\http\Route::__construct()
	 */
	protected $_pattern = '';

	/**
	 * An array of route parameter names (i.e. `{:foo}`) that appear in the URL template.
	 *
	 * @var array
	 * @see lithium\net\http\Route::$_template
	 */
	protected $_keys = [];

	/**
	 * An array of key/value pairs representing the parameters of the route. For keys which match
	 * parameters present in the route template, the corresponding values match the default values
	 * of those parameters. Specifying a default value for a template parameter makes that
	 * parameter optional. Any other pairs specified must match exactly when doing a reverse lookup
	 * in order for the route to match.
	 *
	 * @var array
	 */
	protected $_params = [];

	/**
	 * The array of values that appear in the second parameter of `Router::connect()`, which are
	 * **not** present in the URL template. When matching a route, these parameters must appear
	 * **exactly** as specified here.
	 *
	 * @var array
	 */
	protected $_match = [];

	/**
	 * An array of metadata parameters which must be present in the request in order for the route
	 * to match.
	 *
	 * @var array
	 */
	protected $_meta = [];

	/**
	 * The default values for the keys present in the URL template.
	 *
	 * @var array
	 * @see lithium\net\http\Route::$_template
	 * @see lithium\net\http\Route::$_keys
	 */
	protected $_defaults = [];

	/**
	 * An array of regular expression patterns used in route matching.
	 *
	 * @var array
	 */
	protected $_subPatterns = [];

	/**
	 * An array of parameter names which will persist by default when generating URLs. By default,
	 * the `'controller'` parameter is set to persist, which means that the controller name matched
	 * for a given request will be used to generate all URLs for that request, unless the
	 * `'controller'` parameter is specified in that URL with another value.
	 *
	 * @var array
	 */
	protected $_persist = [];

	/**
	 * Contains a function which will be executed if this route is matched. The function takes the
	 * instance of the associated `Request` object, and the array of matched route parameters, and
	 * must return either the parameters array (which may be modified by the handler) or a
	 * `Response` object, in which case the response will be returned directly. This may be used to
	 * handle redirects, or simple API services.
	 *
	 * ```
	 * new Route([
	 *     'template' => '/photos/{:id:[0-9]+}.jpg',
	 *     'handler' => function($request) {
	 *         return new Response([
	 *             'headers' => ['Content-type' => 'image/jpeg'],
	 *             'body' => Photos::first($request->id)->bytes()
	 *         ]);
	 *     }
	 * }];
	 * ```
	 *
	 * @see lithium\net\http\Route::parse()
	 * @see lithium\net\http\Response
	 * @var callable
	 */
	protected $_handler = null;

	/**
	 * Array of closures used to format route parameters when compiling URLs.
	 *
	 * @see lithium\net\http\Router::formatters()
	 * @var array
	 */
	protected $_formatters = [];

	/**
	 * Auto configuration properties. Also used as the list of properties to return when exporting
	 * this `Route` object to an array.
	 *
	 * @see lithium\net\http\Route::export()
	 * @var array
	 */
	protected $_autoConfig = [
		'template', 'pattern', 'params', 'match', 'meta',
		'keys', 'defaults', 'subPatterns', 'persist', 'handler'
	];

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *         - `'params'` _array_
	 *         - `'template'` _string_
	 *         - `'pattern'` _string_
	 *         - `'match'` _array_
	 *         - `'meta'` _array_
	 *         - `'defaults'` _array_
	 *         - `'keys'` _array_
	 *         - `'persist'` _array_
	 *         - `'handler'` _callable_
	 *         - `'continue'` _boolean_
	 *         - `'modifiers'` _array_
	 *         - `'formatters'` _array_
	 *         - `'unicode'` _boolean_
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'params'   => [],
			'template' => '/',
			'pattern'  => '',
			'match'    => [],
			'meta'     => [],
			'defaults' => [],
			'keys'     => [],
			'persist'  => [],
			'handler'  => null,
			'continue' => false,
			'modifiers' => [],
			'formatters' => [],
			'unicode'  => true
		];
		$this->_autoConfig($config + $defaults, $this->_autoConfig);
		$this->_autoInit($config);
	}

	protected function _init() {
		if (!$this->_config['continue'] && strpos($this->_template, '{:action:') === false) {
			$this->_params += ['action' => 'index'];
		}
		if (!$this->_pattern) {
			$this->compile();
		}
		if (isset($this->_keys['controller']) || isset($this->_params['controller'])) {
			$this->_persist = $this->_persist ?: ['controller'];
		}
	}

	/**
	 * Attempts to parse a request object and determine its execution details.
	 *
	 * @see lithium\net\http\Request
	 * @see lithium\net\http\Request::$params
	 * @see lithium\net\http\Route::$_handler
	 * @param \lithium\net\http\Request $request A request object containing the details of
	 *        the request to be routed.
	 * @param array $options Used to determine the operation of the method, and override certain
	 *              values in the `Request` object:
	 *              - `'url'` _string_: If present, will be used to match in place of the `$url`
	 *                 property of `$request`.
	 * @return object|boolean If this route matches `$request`, returns the request with
	 *         execution details attached to it (inside `Request::$params`). Alternatively when
	 *         a route handler function was used, returns the result of its invocation. Returns
	 *         `false` if the route never matched.
	 */
	public function parse($request, array $options = []) {
		$defaults = ['url' => $request->url];
		$options += $defaults;
		$url = '/' . trim($options['url'], '/');
		$pattern = $this->_pattern;

		if (!preg_match($pattern, $url, $match)) {
			return false;
		}
		foreach ($this->_meta as $key => $compare) {
			$value = $request->get($key);

			if (!($compare == $value || (is_array($compare) && in_array($value, $compare)))) {
				return false;
			}
		}
		foreach ($this->_config['modifiers'] as $key => $modifier) {
			if (isset($match[$key])) {
				$match[$key] = $modifier($match[$key]);
			}
		}

		$result = array_intersect_key($match + ['args' => []], $this->_keys);
		foreach ($result as $key => $value) {
			if ($value === '') {
				unset($result[$key]);
			}
		}
		$result += $this->_params + $this->_defaults;
		$request->params = $result + (array) $request->params;
		$request->persist = array_unique(array_merge($request->persist, $this->_persist));

		if ($this->_handler) {
			$handler = $this->_handler;
			return $handler($request);
		}
		return $request;
	}

	/**
	 * Matches a set of parameters against the route, and returns a URL string
	 * if the route matches the parameters.
	 *
	 * @param array $options An array of parameters.
	 * @return string|boolean URL string on success, else `false` if the route didn't match.
	 */
	public function match(array $options = []) {
		$defaults = ['action' => 'index', 'http:method' => 'GET'];
		$query = null;

		if (!$this->_config['continue']) {
			$options += $defaults;

			if (isset($options['?'])) {
				$query = $options['?'];
				$query = '?' . (is_array($query) ? http_build_query($query) : $query);
				unset($options['?']);
			}
		}
		if (!$options = $this->_matchMethod($options)) {
			return false;
		}
		if (!$options = $this->_matchKeys($options)) {
			return false;
		}
		foreach ($options as $key => $value) {
			if (isset($this->_config['formatters'][$key])) {
				$options[$key] = $this->_config['formatters'][$key]($value);
			}
		}
		foreach ($this->_subPatterns as $key => $pattern) {
			if (isset($options[$key]) && !preg_match("/^{$pattern}$/", $options[$key])) {
				return false;
			}
		}
		$defaults = $this->_defaults + $defaults;

		if ($this->_config['continue']) {
			return $this->_write(['args' => '{:args}'] + $options, $this->_defaults);
		}
		return $this->_write($options, $defaults + ['args' => '']) . $query;
	}

	/**
	 * Returns a boolean value indicating whether this is a continuation route. If `true`, this
	 * route will allow incoming requests to "fall through" to other routes, aggregating parameters
	 * for both this route and any subsequent routes.
	 *
	 * @return boolean Returns the value of `$_config['continue']`.
	 */
	public function canContinue() {
		return $this->_config['continue'];
	}

	/**
	 * Helper used by `Route::match()` which check if the required http method is compatible
	 * with the route.
	 *
	 * @see lithium\net\http\Route::match()
	 * @param array $options An array of URL parameters.
	 * @return mixed On success, returns an updated array of options, On failure, returns `false`.
	 */
	protected function _matchMethod($options) {
		$isMatch = (
			!isset($this->_meta['http:method']) ||
			$options['http:method'] === $this->_meta['http:method']
		);
		if (!$isMatch) {
			return false;
		}
		unset($options['http:method']);
		return $options;
	}

	/**
	 * A helper method used by `match()` to verify that options required to match this route are
	 * present in a URL array.
	 *
	 * @see lithium\net\http\Route::match()
	 * @param array $options An array of URL parameters.
	 * @return mixed On success, returns an updated array of options, merged with defaults. On
	 *         failure, returns `false`.
	 */
	protected function _matchKeys($options) {
		$args = ['args' => 'args'];

		$scope = [];
		if (!empty($options['scope'])) {
			$scope = (array) $options['scope'] + ['params' => []];
			$scope = array_flip($scope['params']);
		}
		unset($options['scope']);

		if (array_intersect_key($options, $this->_match) != $this->_match) {
			return false;
		}
		if ($this->_config['continue']) {
			if (array_intersect_key($this->_keys, $options + $args) != $this->_keys) {
				return false;
			}
		} else {
			if (array_diff_key($options, $this->_match + $this->_keys + $scope)) {
				return false;
			}
		}
		$options += $this->_defaults;
		$base = $this->_keys + $args;
		$match = array_intersect_key($this->_keys, $options) + $args;
		sort($base);
		sort($match);

		if ($base !== $match) {
			return false;
		}
		return $options;
	}

	/**
	 * Writes a set of URL options to this route's template string.
	 *
	 * @param array $options The options to write to this route, with defaults pre-merged.
	 * @param array $defaults The default template options for this route (contains hard-coded
	 *        default values).
	 * @return string Returns the route template string with option values inserted.
	 */
	protected function _write($options, $defaults) {
		$template = $this->_template;
		$trimmed = true;
		$options += ['args' => ''];

		foreach (array_reverse($this->_keys, true) as $key) {
			$value =& $options[$key];
			$pattern = isset($this->_subPatterns[$key]) ? ":{$this->_subPatterns[$key]}" : '';
			$rpl = "{:{$key}{$pattern}}";
			$len = strlen($rpl) * -1;

			if ($trimmed && isset($defaults[$key]) && $value == $defaults[$key]) {
				if (substr($template, $len) == $rpl) {
					$template = rtrim(substr($template, 0, $len), '/');
					continue;
				}
			}
			if ($value === null) {
				$template = str_replace("/{$rpl}", '', $template);
				continue;
			}
			if ($key !== 'args') {
				$trimmed = false;
			}
			$template = str_replace($rpl, $value, $template);
		}
		return $template ?: '/';
	}

	/**
	 * Exports the properties that make up the route to an array, for debugging, caching or
	 * introspection purposes.
	 *
	 * @return array An array containing the properties of the route object, such as URL templates
	 *         and parameter lists.
	 */
	public function export() {
		$result = [];

		foreach ($this->_autoConfig as $key) {
			if ($key === 'formatters') {
				continue;
			}
			$result[$key] = $this->{'_' . $key};
		}
		return $result;
	}

	/**
	 * Compiles URL templates into regular expression patterns for matching against request URLs,
	 * and extracts template parameters into match-parameter arrays.
	 *
	 * @return void
	 */
	public function compile() {
		foreach ($this->_params as $key => $value) {
			if (!strpos($key, ':')) {
				continue;
			}
			unset($this->_params[$key]);
			$this->_meta[$key] = $value;
		}

		$this->_match = $this->_params;

		if ($this->_template === '/' || $this->_template === '') {
			$this->_pattern = '@^/*$@';
			return;
		}
		$this->_pattern = "@^{$this->_template}\$@";
		$match = '@([/.])?\{:([^:}]+):?((?:[^{]+?(?:\{[0-9,]+\})?)*?)\}@S';

		if ($this->_config['unicode']) {
			$this->_pattern .= 'u';
		}
		preg_match_all($match, $this->_pattern, $m);

		if (!$tokens = $m[0]) {
			return;
		}
		$slashes = $m[1];
		$params = $m[2];
		$regexs = $m[3];
		unset($m);
		$this->_keys = [];

		foreach ($params as $i => $param) {
			$this->_keys[$param] = $param;
			$this->_pattern = $this->_regex($regexs[$i], $param, $tokens[$i], $slashes[$i]);
		}
		$this->_defaults += array_intersect_key($this->_params, $this->_keys);
		$this->_match = array_diff_key($this->_params, $this->_defaults);
	}

	/**
	 * Generates a sub-expression capture group for a route regex, using an optional user-supplied
	 * matching pattern.
	 *
	 * @param string $regex An optional user-supplied match pattern. If a route is defined like
	 *               `"/{:id:\d+}"`, then the value will be `"\d+"`.
	 * @param string $param The parameter name which the capture group is assigned to, i.e.
	 *               `'controller'`, `'id'` or `'args'`.
	 * @param string $token The full token representing a matched element in a route template, i.e.
	 *               `'/{:action}'`, `'/{:path:js|css}'`, or `'.{:type}'`.
	 * @param string $prefix The prefix character that separates the parameter from the other
	 *               elements of the route. Usually `'.'` or `'/'`.
	 * @return string Returns the full route template, with the value of `$token` replaced with a
	 *         generated regex capture group.
	 */
	protected function _regex($regex, $param, $token, $prefix) {
		if ($regex) {
			$this->_subPatterns[$param] = $regex;
		} elseif ($param == 'args') {
			$regex = '.*';
		} else {
			$regex = '[^\/]+';
		}

		$req = $param === 'args' || array_key_exists($param, $this->_params) ? '?' : '';

		if ($prefix === '/') {
			$pattern = "(?:/(?P<{$param}>{$regex}){$req}){$req}";
		} elseif ($prefix === '.') {
			$pattern = "\\.(?P<{$param}>{$regex}){$req}";
		} else {
			$pattern = "(?P<{$param}>{$regex}){$req}";
		}
		return str_replace($token, $pattern, $this->_pattern);
	}
}

?>