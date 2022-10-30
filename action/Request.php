<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
	public $params = [];

	/**
	 * Route parameters that should persist when generating URLs in this request context.
	 *
	 * @var array
	 */
	public $persist = [];

	/**
	 * Data found in the HTTP request body, most often populated by `$_POST` and `$_FILES`.
	 *
	 * @var array
	 */
	public $data = [];

	/**
	 * Key/value pairs found encoded in the request URL after '?', populated by `$_GET`.
	 *
	 * @var array
	 */
	public $query = [];

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
	protected $_computed = [];

	/**
	 * Holds the server globals & environment variables.
	 *
	 * @var array
	 */
	protected $_env = [];

	/**
	 * If POST, PUT or PATCH data is coming from an input stream (rather than `$_POST`),
	 * this specified where to read it from.
	 *
	 * @see lithium\action\Request::_init()
	 * @var resource
	 */
	protected $_stream = null;

	/**
	 * Options used to detect features of the request, using `is()`. For example:
	 *
	 * ``` embed:lithium\tests\cases\action\RequestTest::testRequestTypeIsMobile(4-4) ```
	 *
	 * Custom detectors can be added using `detect()`.
	 *
	 * @see lithium\action\Request::is()
	 * @see lithium\action\Request::detect()
	 * @var array
	 */
	protected $_detectors = [
		'mobile'  => ['HTTP_USER_AGENT', null],
		'ajax'    => ['HTTP_X_REQUESTED_WITH', 'XMLHttpRequest'],
		'flash'   => ['HTTP_USER_AGENT', 'Shockwave Flash'],
		'ssl'     => 'HTTPS',
		'dnt'     => ['HTTP_DNT', '1'],
		'get'     => ['REQUEST_METHOD', 'GET'],
		'post'    => ['REQUEST_METHOD', 'POST'],
		'patch'   => ['REQUEST_METHOD', 'PATCH'],
		'put'     => ['REQUEST_METHOD', 'PUT'],
		'delete'  => ['REQUEST_METHOD', 'DELETE'],
		'head'    => ['REQUEST_METHOD', 'HEAD'],
		'options' => ['REQUEST_METHOD', 'OPTIONS']
	];

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = [
		'classes' => 'merge', 'detectors' => 'merge', 'type', 'stream'
	];

	/**
	 * Contains an array of content-types, sorted by quality (the priority which the browser
	 * requests each type).
	 *
	 * @var array
	 */
	protected $_accept = [];

	/**
	 * Holds the value of the current locale, set through the `locale()` method.
	 *
	 * @var string
	 */
	protected $_locale = null;

	/**
	 * Constructor. Adds config values to the public properties when a new object is created,
	 * pulling request data from superglobals if `globals` is set to `true`.
	 *
	 * Normalizes casing of request headers.
	 *
	 * @see lithium\net\http\Request::__construct()
	 * @see lithium\net\http\Message::__construct()
	 * @see lithium\net\Message::__construct()
	 * @param array $config The available configuration options are the following. Further
	 *        options are inherited from the parent classes.
	 *        - `'base'` _string_: Defaults to `null`.
	 *        - `'url'` _string_: Defaults to `null`.
	 *        - `'data'` _array_: Additional data to use when initializing
	 *          the request. Defaults to `[]`.
	 *        - `'stream'` _resource_: Stream to read from in order to get the message
	 *          body when method is POST, PUT or PATCH and data is empty. When not provided
	 *          `php://input` will be used for reading.
	 *        - `'env'` _array_: Defaults to `[]`.
	 *        - `'globals'` _boolean_: Use global variables for populating
	 *          the request's environment and data; defaults to `true`.
	 *        - `'drain'` _boolean_: Enables/disables automatic reading of streams.
	 *          Defaults to `true`. Disable when you're dealing with large binary
	 *          payloads. Note that this will also disable automatic content decoding
	 *          of stream data.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'base' => null,
			'url' => null,
			'env' => [],
			'data' => [],
			'stream' => null,
			'globals' => true,
			'drain' => true,
			'query' => [],
			'headers' => []
		];
		$config += $defaults;

		if ($config['globals']) {
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

			if (!isset($config['scheme'])) {
				$config['scheme'] = strtolower($scheme) . ($this->env('HTTPS') ? 's' : '');
			}
			if (!isset($config['version'])) {
				$config['version'] = $version;
			}
		}
		$this->_base = $this->_base($config['base']);
		$this->url = $this->_url($config['url']);

		$config['headers'] += [
			'Content-Type' => $this->env('CONTENT_TYPE'),
			'Content-Length' => $this->env('CONTENT_LENGTH')
		];

		foreach ($this->_env as $name => $value) {
			if ($name[0] === 'H' && strpos($name, 'HTTP_') === 0) {
				$name = str_replace('_', ' ', substr($name, 5));
				$name = str_replace(' ', '-', ucwords(strtolower($name)));
				$config['headers'] += [$name => $value];
			}
		}

		parent::__construct($config);
	}

	/**
	 * Initializes request object by setting up mobile detectors, determining method and
	 * populating the data property either by using i.e. form data or reading from STDIN in
	 * case binary data is streamed. Will merge any files posted in forms with parsed data.
	 *
	 * @see lithium\action\Request::_parseFiles()
	 */
	protected function _init() {
		parent::_init();

		$mobile = [
			'iPhone', 'MIDP', 'AvantGo', 'BlackBerry', 'J2ME', 'Opera Mini', 'DoCoMo', 'NetFront',
			'Nokia', 'PalmOS', 'PalmSource', 'portalmmm', 'Plucker', 'ReqwirelessWeb', 'iPod',
			'SonyEricsson', 'Symbian', 'UP\.Browser', 'Windows CE', 'Xiino', 'Android'
		];
		if (!empty($this->_config['detectors']['mobile'][1])) {
			$mobile = array_merge($mobile, (array) $this->_config['detectors']['mobile'][1]);
		}
		$this->_detectors['mobile'][1] = $mobile;

		$this->data = (array) $this->_config['data'];

		if (isset($this->data['_method'])) {
			$this->_computed['HTTP_X_HTTP_METHOD_OVERRIDE'] = strtoupper($this->data['_method']);
			unset($this->data['_method']);
		}
		$type = $this->type($this->_config['type'] ?: $this->env('CONTENT_TYPE'));
		$this->method = strtoupper($this->env('REQUEST_METHOD'));
		$hasBody = in_array($this->method, ['POST', 'PUT', 'PATCH']);

		if ($this->_config['drain'] && !$this->body && $hasBody && $type !== 'html') {
			$this->_stream = $this->_stream ?: fopen('php://input', 'r');
			$this->body = stream_get_contents($this->_stream);
			fclose($this->_stream);
		}
		if (!$this->data && $this->body) {
			$this->data = $this->body(null, ['decode' => true, 'encode' => false]);
		}
		$this->body = $this->data;

		if ($this->_config['globals'] && !empty($_FILES)) {
			$this->data = Set::merge($this->data, $this->_parseFiles($_FILES));
		}
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
	 * Defines an artificial `'PLATFORM'` environment variable as either `'IIS'`, `'CGI'`
	 * or `null` to allow checking for the SAPI in a normalized way.
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
				$envs = ['isapi' => 'IIS', 'cgi' => 'CGI', 'cgi-fcgi' => 'CGI'];
				$val = isset($envs[PHP_SAPI]) ? $envs[PHP_SAPI] : null;
			break;
			case 'REMOTE_ADDR':
				$https = [
					'HTTP_X_FORWARDED_FOR',
					'HTTP_PC_REMOTE_ADDR',
					'HTTP_X_REAL_IP'
				];
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
				$val = preg_replace('/^([^.])*/i', "", $this->env('HTTP_HOST'));
			break;
			case 'PHP_AUTH_USER':
			case 'PHP_AUTH_PW':
			case 'PHP_AUTH_DIGEST':
				if (!$header = $this->env('HTTP_AUTHORIZATION')) {
					if (!$header = $this->env('REDIRECT_HTTP_AUTHORIZATION')) {
						return $this->_computed[$key] = $val;
					}
				}
				if (stripos($header, 'basic') === 0) {
					$decoded = base64_decode(substr($header, strlen('basic ')));

					if (strpos($decoded, ':') !== false) {
						list($user, $password) = explode(':', $decoded, 2);

						$this->_computed['PHP_AUTH_USER'] = $user;
						$this->_computed['PHP_AUTH_PW'] = $password;
						return $this->_computed[$key];
					}
				} elseif (stripos($header, 'digest') === 0) {
					return $this->_computed[$key] = substr($header, strlen('digest '));
				}
			default:
				$val = array_key_exists($key, $this->_env) ? $this->_env[$key] : $val;
			break;
		}
		return $this->_computed[$key] = $val;
	}

	/**
	 * Returns information about the type of content that the client is requesting.
	 *
	 * This method may work different then you might think. This is a _convenience_ method
	 * working exclusively with short type names it knows about. Only those types will be
	 * matched. You can tell this method about more types via `Media::type()`.
	 *
	 * Note: In case negotiation fails, `'html'` is used as a fallback type.
	 *
	 * @see lithium\net\http\Media::negotiate()
	 * @param boolean|string $type Optionally a type name i.e. `'json'` or `true`.
	 *        1. If not specified, returns the media type name that the client prefers, using
	 *           a potentially set `type` param, then content negotiation and that fails,
	 *           ultimately falling back and returning the string `'html'`.
	 *        2. If a media type name (string) is passed, returns `true` or `false`, indicating
	 *           whether or not that type is accepted by the client at all.
	 *        3. If `true`, returns the raw content types from the `Accept` header, parsed into
	 *           an array and sorted by client preference.
	 * @return string|boolean|array Returns a type name (i.e. 'json'`) or a
	 *         boolean or an array, depending on the value of `$type`.
	 */
	public function accepts($type = null) {
		$media = $this->_classes['media'];

		if ($type === true) {
			return $this->_accept ?: ($this->_accept = $this->_parseAccept());
		}
		if ($type) {
			return ($media::negotiate($this) ?: 'html') === $type;
		}
		if (isset($this->params['type'])) {
			return $this->params['type'];
		}
		return $media::negotiate($this) ?: 'html';
	}

	/**
	 * Parses the `HTTP_ACCEPT` information the requesting client sends, and converts
	 * that data to an array for consumption by the rest of the framework.
	 *
	 * @return array All the types of content the client can accept.
	 */
	protected function _parseAccept() {
		$accept = $this->env('HTTP_ACCEPT');
		$accept = ($accept && preg_match('/[a-z,-]/i', $accept)) ? explode(',', $accept) : ['text/html'];

		foreach (array_reverse($accept) as $i => $type) {
			unset($accept[$i]);
			list($type, $q) = (explode(';q=', $type, 2) + [$type, 1.0 + $i / 100]);
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
				$accept = [current($type) => 1] + $accept;
			}
		}
		return array_keys($accept);
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
			case in_array($var, ['params', 'data', 'query']):
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
		list($key, $check) = $detector + ['', ''];

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
	 * Creates a _detector_ used with `Request::is()`.  A detector is a boolean check that is
	 * created to determine something about a request.
	 *
	 * A detector check can be either an exact string match or a regular expression match against a
	 * header or environment variable. A detector check can also be a closure that accepts the
	 * `Request` object instance as a parameter.
	 *
	 * For example, to detect whether a request is from an iPhone, you can do the following:
	 * ``` embed:lithium\tests\cases\action\RequestTest::testDetect(11-12) ```
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
			$url = parse_url($ref) + ['path' => ''];
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
	public function to($format, array $options = []) {
		$defaults = [
			'path' => $this->env('base') . '/' . $this->url
		];
		return parent::to($format, $options + $defaults);
	}

	/**
	 * Sets or returns the current locale string. For more information, see
	 * "[Globalization](http://li3.me/docs/book/manual/1.x/common-tasks/globalization)" in the manual.
	 *
	 * @param string $locale An optional locale string like `'en'`, `'en_US'` or `'de_DE'`. If
	 *        specified, will overwrite the existing locale.
	 * @return string Returns the currently set locale string.
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
			$base = preg_replace('/[^\/]+$/', '', $this->env('PHP_SELF'));
		}
		$base = trim(str_replace(["/app/webroot", '/webroot'], '', $base), '/');
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
	 * Normalizes the data from the `$_FILES` superglobal.
	 *
	 * @param array $data Data as formatted in the `$_FILES` superglobal.
	 * @return array Normalized data.
	 */
	protected function _parseFiles($data) {
		$result = [];

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
		foreach ($data as $key => $value) {
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
}

?>