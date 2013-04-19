<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\action;

use lithium\util\Set;
use lithium\util\Validator;

/**
 * A `Request` object is passed into the `Dispatcher`, and is responsible for identifying and
 * storing all the information about an HTTP request made to an application,  including status,
 * headers, and any GET, POST or PUT data, as well as any data returned from the
 * `Router`, after the `Request` object has been matched against a `Route`. Includes a property
 * accessor method (`__get()`) which allows any parameters returned from routing to be accessed as
 * properties of the `Request` object.
 *
 * @see lithium\action\Dispatcher
 * @see lithium\action\Controller
 * @see lithium\net\http\Router
 * @see lithium\net\http\Route
 * @see lithium\action\Request::__get()
 */
class Request extends \lithium\net\http\Request {

	/**
	 * Current url of request.
	 *
	 * @var string
	 */
	public $url = null;

	/**
	 * Params for request.
	 *
	 * @var array
	 */
	public $params = array();

	/**
	 * Route parameters that should persist when generating URLs in this request context.
	 *
	 * @var array
	 */
	public $persist = array();

	/**
	 * Data found in the HTTP request body, most often populated by `$_POST` and `$_FILES`.
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Key/value pairs found encoded in the request URL after '?', populated by `$_GET`.
	 *
	 * @var array
	 */
	public $query = array();

	/**
	 * Base path.
	 *
	 * @var string
	 */
	protected $_base = null;

	/**
	 * Computed environment variables for the request. Retrieved with env().
	 *
	 * @var array
	 * @see lithium\action\Request::env()
	 */
	protected $_computed = array();

	/**
	 * Holds the server globals & environment variables.
	 *
	 * @var array
	 */
	protected $_env = array();

	/**
	 * If POST / PUT data is coming from an input stream (rather than `$_POST`), this specified
	 * where to read it from.
	 *
	 * @var stream
	 */
	protected $_stream = null;

	/**
	 * Options used to detect request type.
	 *
	 * @see lithium\action\Request::detect()
	 * @var array
	 */
	protected $_detectors = array(
		'mobile'  => array('HTTP_USER_AGENT', null),
		'ajax'    => array('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest'),
		'flash'   => array('HTTP_USER_AGENT', 'Shockwave Flash'),
		'ssl'     => 'HTTPS',
		'get'     => array('REQUEST_METHOD', 'GET'),
		'post'    => array('REQUEST_METHOD', 'POST'),
		'put'     => array('REQUEST_METHOD', 'PUT'),
		'delete'  => array('REQUEST_METHOD', 'DELETE'),
		'head'    => array('REQUEST_METHOD', 'HEAD'),
		'options' => array('REQUEST_METHOD', 'OPTIONS')
	);

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array(
		'classes' => 'merge', 'detectors' => 'merge', 'type', 'stream'
	);

	/**
	 * Contains an array of content-types, sorted by quality (the priority which the browser
	 * requests each type).
	 *
	 * @var array
	 */
	protected $_acceptContent = array();

	/**
	 * Holds the value of the current locale, set through the `locale()` method.
	 *
	 * @var string
	 */
	protected $_locale = null;

	/**
	 * Adds config values to the public properties when a new object is created, pulling
	 * request data from superglobals if `globals` is set to `true`.
	 *
	 * @param array $config Configuration options : default values are:
	 *        - `'base'` _string_: null
	 *        - `'url'` _string_: null
	 *        - `'protocol'` _string_: null
	 *        - `'version'` _string_: '1.1'
	 *        - `'method'` _string_: 'GET'
	 *        - `'scheme'` _string_: 'http'
	 *        - `'host'` _string_: 'localhost'
	 *        - `'port'` _integer_: null
	 *        - `'username'` _string_: null
	 *        - `'password'` _string_: null
	 *        - `'path'` _string_: null
	 *        - `'query'` _array_: array()
	 *        - `'headers'` _array_: array()
	 *        - `'type'` _string_: null
	 *        - `'auth'` _mixed_: null
	 *        - `'body'` _mixed_: null
	 *        - `'data'` _array_: array()
	 *        - `'env'` _array_: array()
	 *        - `'globals'` _boolean_: true
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'base' => null,
			'url' => null,
			'env' => array(),
			'query' => array(),
			'data' => array(),
			'globals' => true
		);
		$config += $defaults;

		if ($config['globals'] === true) {
			if (isset($_SERVER)) {
				$config['env'] += $_SERVER;
			}
			if (isset($_ENV)) {
				$config['env'] += $_ENV;
			}
			if (isset($_GET)) {
				$config['query'] += $_GET;
			}
			if (isset($_POST)) {
				$config['data'] += $_POST;
			}
		}

		$this->_env = $config['env'];

		if (!isset($config['host'])) {
			$config['host'] = $this->env('HTTP_HOST');
		}

		if (!isset($config['protocol'])) {
			$config['protocol'] = $this->env('SERVER_PROTOCOL');
		}

		if ($config['protocol'] && strpos($config['protocol'], '/')) {
			list($scheme, $version) = explode('/', $config['protocol']);
			$https = ($this->env('HTTPS') ? 's' : '');
			$scheme = strtolower($scheme) . $https;
			if (!isset($config['scheme'])) {
				$config['scheme'] = $scheme;
			}
			if (!isset($config['version'])) {
				$config['version'] = $version;
			}
		}

		$this->_base = $this->_base($config['base']);
		$this->url = $this->_url($config['url']);

		parent::__construct($config);
	}

	/**
	 * Initialize request object
	 *
	 * Defines an artificial `'PLATFORM'` environment variable as either `'IIS'`, `'CGI'` or `null`
	 * to allow checking for the SAPI in a normalized way.
	 */
	protected function _init() {
		parent::_init();
		$mobile = array(
			'iPhone', 'MIDP', 'AvantGo', 'BlackBerry', 'J2ME', 'Opera Mini', 'DoCoMo', 'NetFront',
			'Nokia', 'PalmOS', 'PalmSource', 'portalmmm', 'Plucker', 'ReqwirelessWeb', 'iPod',
			'SonyEricsson', 'Symbian', 'UP\.Browser', 'Windows CE', 'Xiino', 'Android'
		);
		if (!empty($this->_config['detectors']['mobile'][1])) {
			$mobile = array_merge($mobile, (array) $this->_config['detectors']['mobile'][1]);
		}
		$this->_detectors['mobile'][1] = $mobile;

		$this->data = $this->_config['data'];
		if (isset($this->data['_method'])) {
			$this->_computed['HTTP_X_HTTP_METHOD_OVERRIDE'] = strtoupper($this->data['_method']);
			unset($this->data['_method']);
		}
		$type = $this->type($this->_config['type'] ?: $this->env('CONTENT_TYPE'));
		$this->method = $method = strtoupper($this->env('REQUEST_METHOD'));
		$hasBody = in_array($method, array('POST', 'PUT', 'PATCH'));
		if (!$this->body && $hasBody && $type !== 'html') {
			$this->_stream = $this->_stream ?: fopen('php://input', 'r');
			$this->body = stream_get_contents($this->_stream);
			fclose($this->_stream);
		}
		if (!$this->data && $this->body) {
			$this->data = $this->body(null, array('decode' => true, 'encode' => false));
		}
		$this->body = $this->data;
		$this->data = Set::merge((array) $this->data, $this->_parseFiles());
	}

	/**
	 * Allows request parameters to be accessed as object properties, i.e. `$this->request->action`
	 * instead of `$this->request->params['action']`.
	 *
	 * @see lithium\action\Request::$params
	 * @param string $name The property name/parameter key to return.
	 * @return mixed Returns the value of `$params[$name]` if it is set, otherwise returns null.
	 */
	public function __get($name) {
		if (isset($this->params[$name])) {
			return $this->params[$name];
		}
	}

	/**
	 * Allows request parameters to be checked using short-hand notation. See the `__get()` method
	 * for more details.
	 *
	 * @see lithium\action\Request::__get()
	 * @param string $name The name of the request parameter to check.
	 * @return boolean Returns true if the key in `$name` is set in the `$params` array, otherwise
	 *         `false`.
	 */
	public function __isset($name) {
		return isset($this->params[$name]);
	}

	/**
	 * Queries PHP's environment settings, and provides an abstraction for standardizing expected
	 * environment values across varying platforms, as well as specify custom environment flags.
	 *
	 * @param string $key The environment variable required.
	 * @return string The requested variables value.
	 * @todo Refactor to lazy-load environment settings
	 */
	public function env($key) {
		if (array_key_exists($key, $this->_computed)) {
			return $this->_computed[$key];
		}

		$val = null;
		if (!empty($this->_env[$key])) {
			$val = $this->_env[$key];
			if ($key !== 'REMOTE_ADDR' && $key !== 'HTTPS' && $key !== 'REQUEST_METHOD') {
				return $this->_computed[$key] = $val;
			}
		}

		switch ($key) {
			case 'BASE':
			case 'base':
				$val = $this->_base($this->_config['base']);
			break;
			case 'HTTP_HOST':
				$val = 'localhost';
			break;
			case 'SERVER_PROTOCOL':
				$val = 'HTTP/1.1';
			break;
			case 'REQUEST_METHOD':
				if ($this->env('HTTP_X_HTTP_METHOD_OVERRIDE')) {
					$val = $this->env('HTTP_X_HTTP_METHOD_OVERRIDE');
				} elseif (isset($this->_env['REQUEST_METHOD'])) {
					$val = $this->_env['REQUEST_METHOD'];
				} else {
					$val = 'GET';
				}
			break;
			case 'CONTENT_TYPE':
				$val = 'text/html';
			break;
			case 'PLATFORM':
				$envs = array('isapi' => 'IIS', 'cgi' => 'CGI', 'cgi-fcgi' => 'CGI');
				$val = isset($envs[PHP_SAPI]) ? $envs[PHP_SAPI] : null;
			break;
			case 'REMOTE_ADDR':
				$https = array(
					'HTTP_X_FORWARDED_FOR',
					'HTTP_PC_REMOTE_ADDR',
					'HTTP_X_REAL_IP'
				);
				foreach ($https as $altKey) {
					if ($addr = $this->env($altKey)) {
						list($val) = explode(', ', $addr);
						break;
					}
				}
			break;
			case 'SCRIPT_NAME':
				if ($this->env('PLATFORM') === 'CGI') {
					return $this->env('SCRIPT_URL');
				}
				$val = null;
			break;
			case 'HTTPS':
				if (isset($this->_env['SCRIPT_URI'])) {
					$val = strpos($this->_env['SCRIPT_URI'], 'https://') === 0;
				} elseif (isset($this->_env['HTTPS'])) {
					$val = (!empty($this->_env['HTTPS']) && $this->_env['HTTPS'] !== 'off');
				} else {
					$val = false;
				}
			break;
			case 'SERVER_ADDR':
				if (empty($this->_env['SERVER_ADDR']) && !empty($this->_env['LOCAL_ADDR'])) {
					$val = $this->_env['LOCAL_ADDR'];
				} elseif (isset($this->_env['SERVER_ADDR'])) {
					$val = $this->_env['SERVER_ADDR'];
				}
			break;
			case 'SCRIPT_FILENAME':
				if ($this->env('PLATFORM') === 'IIS') {
					$val = str_replace('\\\\', '\\', $this->env('PATH_TRANSLATED'));
				} elseif (isset($this->_env['DOCUMENT_ROOT']) && isset($this->_env['PHP_SELF'])) {
					$val = $this->_env['DOCUMENT_ROOT'] . $this->_env['PHP_SELF'];
				}
			break;
			case 'DOCUMENT_ROOT':
				$fileName = $this->env('SCRIPT_FILENAME');
				$offset = (!strpos($this->env('SCRIPT_NAME'), '.php')) ? 4 : 0;
				$offset = strlen($fileName) - (strlen($this->env('SCRIPT_NAME')) + $offset);
				$val = substr($fileName, 0, $offset);
			break;
			case 'PHP_SELF':
				$val = '/';
			break;
			case 'CGI':
			case 'CGI_MODE':
				$val = $this->env('PLATFORM') === 'CGI';
			break;
			case 'HTTP_BASE':
				$val = preg_replace('/^([^.])*/i', null, $this->env('HTTP_HOST'));
			break;
			default:
				$val = array_key_exists($key, $this->_env) ? $this->_env[$key] : $val;
			break;
		}
		return $this->_computed[$key] = $val;
	}

	/**
	 * Returns information about the type of content that the client is requesting.
	 *
	 * @see lithium\net\http\Media::negotiate()
	 * @param $type mixed If not specified, returns the media type name that the client prefers,
	 *        using content negotiation. If a media type name (string) is passed, returns `true` or
	 *        `false`, indicating whether or not that type is accepted by the client at all.
	 *        If `true`, returns the raw content types from the `Accept` header, parsed into an array
	 *        and sorted by client preference.
	 * @return string Returns a simple type name if the type is registered (i.e. `'json'`), or
	 *         a fully-qualified content-type if not (i.e. `'image/jpeg'`), or a boolean or array,
	 *         depending on the value of `$type`.
	 */
	public function accepts($type = null) {
		if ($type === true) {
			return $this->_parseAccept();
		}
		if (!$type && isset($this->params['type'])) {
			return $this->params['type'];
		}
		$media = $this->_classes['media'];
		return $media::negotiate($this) ?: 'html';
	}

	protected function _parseAccept() {
		if ($this->_acceptContent) {
			return $this->_acceptContent;
		}
		$accept = $this->env('HTTP_ACCEPT');
		$accept = (preg_match('/[a-z,-]/i', $accept)) ? explode(',', $accept) : array('text/html');

		foreach (array_reverse($accept) as $i => $type) {
			unset($accept[$i]);
			list($type, $q) = (explode(';q=', $type, 2) + array($type, 1.0 + $i / 100));
			$accept[$type] = ($type === '*/*') ? 0.1 : floatval($q);
		}
		arsort($accept, SORT_NUMERIC);

		if (isset($accept['application/xhtml+xml']) && $accept['application/xhtml+xml'] >= 1) {
			unset($accept['application/xml']);
		}
		$media = $this->_classes['media'];

		if (isset($this->params['type']) && ($handler = $media::type($this->params['type']))) {
			if (isset($handler['content'])) {
				$type = (array) $handler['content'];
				$accept = array(current($type) => 1) + $accept;
			}
		}
		return $this->_acceptContent = array_keys($accept);
	}

	/**
	 * This method allows easy extraction of any request data using a prefixed key syntax. By
	 * passing keys in the form of `'prefix:key'`, it is possible to query different information of
	 * various different types, including GET and POST data, and server environment variables. The
	 * full list of prefixes is as follows:
	 *
	 * - `'data'`: Retrieves values from POST data.
	 * - `'params'`: Retrieves query parameters returned from the routing system.
	 * - `'query'`: Retrieves values from GET data.
	 * - `'env'`: Retrieves values from the server or environment, such as `'env:https'`, or custom
	 *   environment values, like `'env:base'`. See the `env()` method for more info.
	 * - `'http'`: Retrieves header values (i.e. `'http:accept'`), or the HTTP request method (i.e.
	 *   `'http:method'`).
	 *
	 * This method is used in several different places in the framework in order to provide the
	 * ability to act conditionally on different aspects of the request. See `Media::type()` (the
	 * section on content negotiation) and the routing system for more information.
	 *
	 *  _Note_: All keys should be _lower-cased_, even when getting HTTP headers.
	 *
	 * @see lithium\action\Request::env()
	 * @see lithium\net\http\Media::type()
	 * @see lithium\net\http\Router
	 * @param string $key A prefixed key indicating what part of the request data the requested
	 *        value should come from, and the name of the value to retrieve, in lower case.
	 * @return string Returns the value of a GET, POST, routing or environment variable, or an
	 *         HTTP header or method name.
	 */
	public function get($key) {
		list($var, $key) = explode(':', $key);

		switch (true) {
			case in_array($var, array('params', 'data', 'query')):
				return isset($this->{$var}[$key]) ? $this->{$var}[$key] : null;
			case ($var === 'env'):
				return $this->env(strtoupper($key));
			case ($var === 'http' && $key === 'method'):
				return $this->env('REQUEST_METHOD');
			case ($var === 'http'):
				return $this->env('HTTP_' . strtoupper($key));
		}
	}

	/**
	 * Provides a simple syntax for making assertions about the properties of a request.
	 * By default, the `Request` object is configured with several different types of assertions,
	 * which are individually known as _detectors_. Detectors are invoked by calling the `is()` and
	 * passing the name of the detector flag, i.e. `$request->is('<name>')`, which returns `true` or
	 * `false`, depending on whether or not the the properties (usually headers or data) contained
	 * in the request match the detector. The default detectors include the following:
	 *
	 * - `'mobile'`: Uses a regular expression to match common mobile browser user agents.
	 * - `'ajax'`: Checks to see if the `X-Requested-With` header is present, and matches the value
	 *    `'XMLHttpRequest'`.
	 * - `'flash'`: Checks to see if the user agent is `'Shockwave Flash'`.
	 * - `'ssl'`: Verifies that the request is SSL-secured.
	 * - `'get'` / `'post'` / `'put'` / `'delete'` / `'head'` / `'options'`: Checks that the HTTP
	 *   request method matches the one specified.
	 *
	 * In addition to the above, this method also accepts media type names (see `Media::type()`) to
	 * make assertions against the format of the request body (for POST or PUT requests), i.e.
	 * `$request->is('json')`. This will return `true` if the client has made a POST request with
	 * JSON data.
	 *
	 * For information about adding custom detectors or overriding the ones in the core, see the
	 * `detect()` method.
	 *
	 * While these detectors are useful in controllers or other similar contexts, they're also
	 * useful when performing _content negotiation_, which is the process of modifying the response
	 * format to suit the client (see the `'conditions'` field of the `$options` parameter in
	 * `Media::type()`).
	 *
	 * @see lithium\action\Request::detect()
	 * @see lithium\net\http\Media::type()
	 * @param string $flag The name of the flag to check, which should be the name of a valid
	 *        detector (that is either built-in or defined with `detect()`).
	 * @return boolean Returns `true` if the detector check succeeds (see the details for the
	 *         built-in detectors above, or `detect()`), otherwise `false`.
	 */
	public function is($flag) {
		$media = $this->_classes['media'];

		if (!isset($this->_detectors[$flag])) {
			if (!in_array($flag, $media::types())) {
				return false;
			}
			return $this->type() === $flag;
		}
		$detector = $this->_detectors[$flag];

		if (!is_array($detector) && is_callable($detector)) {
			return $detector($this);
		}
		if (!is_array($detector)) {
			return (boolean) $this->env($detector);
		}
		list($key, $check) = $detector + array('', '');

		if (is_array($check)) {
			$check = '/' . join('|', $check) . '/i';
		}
		if (Validator::isRegex($check)) {
			return (boolean) preg_match($check, $this->env($key));
		}
		return ($this->env($key) === $check);
	}

	/**
	 * Sets/Gets the content type. If `'type'` is null, the method will attempt to determine the
	 * type from the params, then from the environment setting
	 *
	 * @param string $type a full content type i.e. `'application/json'` or simple name `'json'`
	 * @return string A simple content type name, i.e. `'html'`, `'xml'`, `'json'`, etc., depending
	 *         on the content type of the request.
	 */
	public function type($type = null) {
		if (!$type && !empty($this->params['type'])) {
			$type = $this->params['type'];
		}
		return parent::type($type);
	}

	/**
	 * Expands on `\net\http\Message::headers()` by translating field names and values to those
	 * provided by the server environment.
	 *
	 * @param string $key
	 * @param string $value
	 * @param boolean $replace
	 * @return mixed
	 */
	public function headers($key = null, $value = null, $replace = true) {
		if (is_string($key) && !isset($this->headers[$key]) && $value === null) {
			$env = strtoupper(str_replace('-', '_', $key));
			if (!in_array($env, array('CONTENT_TYPE', 'CONTENT_LENGTH'))) {
				$env = 'HTTP_' . $env;
			}
			if (!empty($this->_env[$env])) {
				$this->headers($key, $this->_env[$env]);
				return $this->_env[$env];
			}
		}
		if (!$key) {
			foreach ($this->_env as $name => $value) {
				if (substr($name, 0, 5) == 'HTTP_') {
					$name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
					$this->headers($name, $value);
				}
			}
			$this->headers('Content-Type');
			$this->headers('Content-Length');
		}
		return parent::headers($key, $value, $replace);
	}

	/**
	 * Creates a _detector_ used with `Request::is()`.  A detector is a boolean check that is
	 * created to determine something about a request.
	 *
	 * A detector check can be either an exact string match or a regular expression match against a
	 * header or environment variable. A detector check can also be a closure that accepts the
	 * `Request` object instance as a parameter.
	 *
	 * For example, to detect whether a request is from an iPhone, you can do the following:
	 * {{{ embed:lithium\tests\cases\action\RequestTest::testDetect(11-12) }}}
	 *
	 * @see lithium\action\Request::is()
	 * @param string $flag The name of the detector check. Used in subsequent calls to `Request::is()`.
	 * @param mixed $detector Detectors can be specified in four different ways:
	 *        - The name of an HTTP header or environment variable. If a string, calling the detector
	 *          will check that the header or environment variable exists and is set to a non-empty
	 *          value.
	 *        - A two-element array containing a header/environment variable name, and a value to match
	 *          against. The second element of the array must be an exact match to the header or
	 *          variable value.
	 *        - A two-element array containing a header/environment variable name, and a regular
	 *          expression that matches against the value, as in the example above.
	 *        - A closure which accepts an instance of the `Request` object and returns a boolean
	 *          value.
	 * @return void
	 */
	public function detect($flag, $detector = null) {
		if (is_array($flag)) {
			$this->_detectors = $flag + $this->_detectors;
		} else {
			$this->_detectors[$flag] = $detector;
		}
	}

	/**
	 * Gets the referring URL of this request.
	 *
	 * @param string $default Default URL to use if HTTP_REFERER cannot be read from headers.
	 * @param boolean $local If true, restrict referring URLs to local server.
	 * @return string Referring URL.
	 */
	public function referer($default = null, $local = false) {
		if ($ref = $this->env('HTTP_REFERER')) {
			if (!$local) {
				return $ref;
			}
			$url = parse_url($ref) + array('path' => '');
			if (empty($url['host']) || $url['host'] === $this->env('HTTP_HOST')) {
				$ref = $url['path'];
				if (!empty($url['query'])) {
					$ref .= '?' . $url['query'];
				}
				if (!empty($url['fragment'])) {
					$ref .= '#' . $url['fragment'];
				}
				return $ref;
			}
		}
		return ($default !== null) ? $default : '/';
	}

	/**
	 * Overrides `lithium\net\http\Request::to()` to provide the correct options for generating
	 * URLs. For information about this method, see the parent implementation.
	 *
	 * @see lithium\net\http\Request::to()
	 * @param string $format The format to convert to.
	 * @param array $options Override options.
	 * @return mixed The return value type depends on `$format`.
	 */
	public function to($format, array $options = array()) {
		$defaults = array(
			'path' => $this->env('base') . '/' . $this->url
		);
		return parent::to($format, $options + $defaults);
	}

	/**
	 * Sets or returns the current locale string. For more information, see
	 * "[Globalization](http://lithify.me/docs/manual/07_globalization)" in the manual.
	 *
	 * @param string $locale An optional locale string like `'en'`, `'en_US'` or `'de_DE'`. If
	 *        specified, will overwrite the existing locale.
	 * @return Returns the currently set locale string.
	 */
	public function locale($locale = null) {
		if ($locale) {
			$this->_locale = $locale;
		}
		if ($this->_locale) {
			return $this->_locale;
		}
		if (isset($this->params['locale'])) {
			return $this->params['locale'];
		}
	}

	/**
	 * Find the base path of the current request.
	 *
	 * @param string $base The base path. If `null`, `'PHP_SELF'` will be used instead.
	 * @return string
	 */
	protected function _base($base = null) {
		if ($base === null) {
			$base = dirname($this->env('PHP_SELF'));
		}
		$base = trim(str_replace(array("/app/webroot", '/webroot'), '', $base), '/');
		return $base ? '/' . $base : '';
	}

	/**
	 * Extract the url from `REQUEST_URI` && `PHP_SELF` environment variables.
	 *
	 * @param  string The base url If `null`, environment variables will be used instead.
	 * @return string
	 */
	protected function _url($url = null) {
		if ($url !== null) {
			return '/' . trim($url, '/');
		} elseif ($uri = $this->env('REQUEST_URI')) {
			list($uri) = explode('?', $uri, 2);
			$base = '/^' . preg_quote($this->_base, '/') . '/';
			return '/' . trim(preg_replace($base, '', $uri), '/') ?: '/';
		}
		return '/';
	}

	/**
	 * Normalize the data in $_FILES
	 *
	 * @return array
	 */
	protected function _parseFiles() {
		if (!empty($_FILES)) {
			$result = array();

			$normalize = function($key, $value) use ($result, &$normalize){
				foreach ($value as $param => $content) {
					foreach ($content as $num => $val) {
						if (is_numeric($num)) {
							$result[$key][$num][$param] = $val;
							continue;
						}
						if (is_array($val)) {
							foreach ($val as $next => $one) {
								$result[$key][$num][$next][$param] = $one;
							}
							continue;
						}
						$result[$key][$num][$param] = $val;
					}
				}
				return $result;
			};
			foreach ($_FILES as $key => $value) {
				if (isset($value['name'])) {
					if (is_string($value['name'])) {
						$result[$key] = $value;
						continue;
					}
					if (is_array($value['name'])) {
						$result += $normalize($key, $value);
					}
				}
			}
			return $result;
		}
		return array();
	}
}

?>