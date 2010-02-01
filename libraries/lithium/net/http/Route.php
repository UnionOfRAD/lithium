<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\http;

/**
 * The `Route` class represents a single URL pattern which is matched against incoming requests, in
 * order to determine the correct controller and action that an HTTP request should be dispatched
 * to.
 *
 * Typically, `Route` objects are created and handled through the `lithium\net\http\Router` class,
 * as follows:
 *
 * {{{// This instantiates a Route object behind the scenes, and adds it to Router's collection:
 * Router::connect("/{:controller}/{:action}");
 *
 * // This matches a set of parameters against all Route objects contained in Router, and if a match
 * // is found, returns a string URL with parameters inserted into the URL pattern:
 * Router::match(array("controller" => "users", "action" => "login")); // returns "/users/login"
 * }}}
 *
 * For more advanced routing, however, you can directly instantiate a `Route` object, a subclass,
 * or any class that implements `parse()` and `match()` (see the documentation for each individual
 * method) and configure it manually -- if, for example, you want the route to match different
 * incoming URLs than it generates.
 *
 * {{{$route = new Route(array(
 *		'template' => '/users/{:user}',
 *		'pattern' => '@^/u(?:sers)?(?:/(?P<user>[^\/]+))$@',
 *		'params' => array('controller' => 'users', 'action' => 'index'),
 *		'match' => array('controller' => 'users', 'action' => 'index'),
 *		'defaults' => array('controller' => 'users'),
 *		'keys' => array('user' => 'user'),
 *		'options' => array('compile' => false, 'wrap' => false)
 * ));
 * Router::connect($route); // this will match '/users/<username>' or '/u/<username>'.
 * }}}
 *
 * For additional information on the `'options'` constructor key, see
 * `lithium\net\http\Route::compile()`. To learn more about Lithium's routing system, see
 * `lithium\net\http\Router`.
 *
 * @see lithium\net\http\Route::compile()
 * @see lithium\net\http\Router
 */
class Route extends \lithium\core\Object {

	/**
	 * The URL template string that the route matches.
	 *
	 * This string can contain fixed elements, i.e. `"/admin"`, capture elements,
	 * i.e. `"/{:controller}"`, capture elements optionally paired with regular expressions or
	 * named regular expression patterns, i.e. `"/{:id:\d+}"` or `"/{:id:ID}"`, wildcard captures
	 * i.e. `"**"`, or any combination thereof, i.e. `"/admin/{:controller}/{:id:ID}/**"`.
	 *
	 * @var string
	 */
	protected $_template = '';

	/**
	 * The regular expression used to match URLs.
	 *
	 * This regular expression is typically 'compiled' down from the higher-level syntax used in
	 * `$_template`, but can be set manually with compilation turned off in the constructor for
	 * extra control or if you are using pre-compiled `Route` objects.
	 *
	 * @var string
	 * @see lithium\net\http\Route::$_template
	 * @see lithium\net\http\Route::__construct()
	 */
	protected $_pattern = '';

	protected $_keys = array();

	protected $_params = array();

	protected $_match = array();

	protected $_defaults = array();

	protected $_subPatterns = array();

	protected $_autoConfig = array(
		'template', 'pattern', 'keys', 'params', 'match', 'defaults', 'subPatterns'
	);

	public function __construct($config = array()) {
		$defaults = array(
			'params' => array(),
			'template' => '/',
			'pattern' => '^[\/]*$',
			'match' => array(),
			'defaults' => array(),
			'keys' => array(),
			'options' => array()
		);
		parent::__construct((array) $config + $defaults);
	}

	protected function _init() {
		parent::_init();
		$this->_pattern = $this->_pattern ?: rtrim($this->_template, '/');
		$this->_params += array('action' => 'index');
		$this->compile($this->_config['options']);
	}

	/**
	 * Attempts to parse a request object and determine its execution details.
	 *
	 * @param object $request A request object, usually an instance of `lithium\net\http\Request`,
	 *        containing the details of the request to be routed.
	 * @return mixed If this route matches `$request`, returns an array of the execution details
	 *         contained in the route, otherwise returns false.
	 */
	public function parse($request) {
		$url = '/' . trim($request->url, '/');

		if (preg_match($this->_pattern, $url, $match)) {
			$match['args'] = isset($match['args']) ?  explode('/', $match['args']) : array();
			$result = array_intersect_key($match, $this->_keys) + $this->_params + $this->_defaults;
			$result['action'] = $result['action'] ?: 'index';
			return $result;
		}
		return false;
	}

	/**
	 * Matches a set of parameters against the route, and returns a URL string if the route matches
	 * the parameters, or false if it does not match.
	 *
	 * @param string $options
	 * @param string $context
	 * @return mixed
	 */
	public function match($options = array(), $context = null) {
		$defaults = array('action' => 'index');
		$options += $defaults;

		if (array_intersect_key($options, $this->_match) !== $this->_match) {
			return false;
		}
		if (array_diff_key(array_diff_key($options, $this->_match), $this->_keys) !== array()) {
			return false;
		}
		$options += $this->_defaults;
		$args = array('args' => 'args');

		if (array_intersect_key($this->_keys, $options) + $args !== $this->_keys + $args) {
			return false;
		}
		return $this->_write($options, $defaults + $this->_defaults + array('args' => ''));
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

		if (isset($options['args']) && is_array($options['args'])) {
			$options['args'] = join('/', $options['args']);
		}

		foreach (array_reverse($options + array('args' => ''), true) as $key => $value) {
			$rpl = "{:{$key}}";
			$len = - strlen($rpl);

			if ($trimmed && isset($defaults[$key]) && $value == $defaults[$key]) {
				if (substr($template, $len) == $rpl) {
					$template = rtrim(substr($template, 0, $len), '/');
					continue;
				}
			}
			$template = str_replace($rpl, $value, $template);
			$trimmed = ($key == 'args') ? $trimmed :  false;
		}
		return $template;
	}

	/**
	 * Exports the properties that make up the route to an array, for debugging, caching or
	 * introspection purposes.
	 *
	 * @return array An array containing the properties of the route object, such as URL templates
	 *         and parameter lists.
	 */
	public function export() {
		$result = array();
		$keys = array('template', 'pattern', 'keys', 'params', 'match', 'defaults', 'subPatterns');

		foreach ($keys as $key) {
			$result[$key] = $this->{'_' . $key};
		}
		return $result;
	}

	/**
	 * Compiles URL templates into regular expression patterns for matching against request URLs,
	 * and extracts template parameters into match-parameter arrays.
	 *
	 * @param array $options
	 * @return void
	 */
	public function compile($options = array()) {
		$defaults = array('wrap' => true, 'compile' => true);
		$options += $defaults;

		if (!$options['compile']) {
			$this->_pattern = $options['wrap'] ? '@^' . $this->_pattern . '$@' : $this->_pattern;
			return;
		}

		$this->_match = $this->_params;
		$this->_pattern = $this->_template;
		$this->_pattern = $options['wrap'] ? '@^' . $this->_pattern . '$@' : $this->_pattern;

		if ($this->_template === '/' || $this->_template === '') {
			return;
		}
		preg_match_all('/(?:\{:(?P<params>[^}]+)\})/', $this->_pattern, $keys);

		if (empty($keys['params'])) {
			return;
		}
		$shortKeys = array();
		$this->_pattern = str_replace('.{', '\.{', $this->_pattern);

		if (strpos($this->_pattern, '{:args}') !== false) {
			$this->_pattern = str_replace('/{:args}', '(?:/(?P<args>.*))?', $this->_pattern);
			$this->_pattern = str_replace('{:args}', '(?:/(?P<args>.*))?', $this->_pattern);
			$this->_keys['args'] = 'args';
		}

		foreach ($keys['params'] as $i => $param) {
			$paramName = $param;

			if (strpos($param, ':')) {
				list($paramName, $pattern) = explode(':', $param, 2);
				$this->_subPatterns[$paramName] = $pattern;
				$shortKeys[$i] = $paramName;
			} else {
				$pattern = '[^\/]+';
			}
			$req = (array_key_exists($paramName, $this->_params) ? '?' : '');

			$regex = "(?P<{$paramName}>{$pattern}){$req}";
			$this->_pattern = str_replace("/{:{$param}}", "(?:/{$regex}){$req}", $this->_pattern);
			$this->_pattern = str_replace("{:{$param}}", $regex, $this->_pattern);
		}
		$shortKeys += $keys['params'];
		ksort($shortKeys);

		$this->_keys = array_combine($shortKeys, $shortKeys);
		$this->_defaults = array_intersect_key($this->_params, $this->_keys);
		$this->_match = array_diff_key($this->_params, $this->_defaults);
	}
}

?>