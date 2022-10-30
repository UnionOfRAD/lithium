<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\net\http;

use lithium\util\Text;
use UnexpectedValueException;

/**
 * Facilitates HTTP request creation by assembling connection and path info, `GET` and `POST` data,
 * and authentication credentials in a single, stateful object.
 */
class Request extends \lithium\net\http\Message {

	/**
	 * The method of the request, typically one of the following: `GET`, `POST`, `PUT`, `DELETE`,
	 * `OPTIONS`, `HEAD`, `TRACE` or `CONNECT`.
	 *
	 * @var string
	 */
	public $method = 'GET';

	/**
	 * Key/value pairs found encoded in the URL after the '?'.
	 *
	 * @var array
	 */
	public $query = [];

	/**
	 * Authentication type and parameters for HTTP Basic or Digest.
	 *
	 * Any array with a 'nonce' attribute implies Digest authentication; all other non-empty values
	 * for imply Basic authentication.
	 *
	 * @see lithium\net\http\Auth::encode()
	 * @var mixed
	 */
	public $auth = null;

	/**
	 * Cookies.
	 *
	 * @var array
	 */
	public $cookies = [];

	/**
	 * An array of closures representing various formats this object can be exported to.
	 *
	 * @var array
	 */
	protected $_formats = [];

	/**
	 * Constructor. Adds config values to the public properties when a new object is created.
	 *
	 * @see lithium\net\http\Message::__construct()
	 * @see lithium\net\Message::__construct()
	 * @param array $config The available configuration options are the following. Further
	 *        options are inherited from the parent classes.
	 *        - `'method'` _string_: Defaults to `'GET'`.
	 *        - `'path'` _string_: Defaults to `null`.
	 *        - `'query'` _array_: Defaults to `[]`.
	 *        - `'cookies'` _array_: Defaults to `[]`.
	 *        - `'type'` _string_: Defaults to `null`.
	 *        - `'auth'` _mixed_: Defaults to `null`.
	 *        - `'proxy'` _string_: Defaults to `null`.
	 *        - `'ignoreErrors'` _boolean_: Defaults to `true`.
	 *        - `'followLocation'` _boolean_: Defaults to `true`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'method' => 'GET',
			'query' => [],
			'cookies' => [],
			'type' => null,
			'auth' => null,
			'proxy' => null,
			'ignoreErrors' => true,
			'followLocation' => true
		];
		$config += $defaults;

		$this->method  = $config['method'];
		$this->query   = $config['query'];
		$this->auth    = $config['auth'];
		parent::__construct($config);

		$this->headers = [
			'Host' => $this->port ? "{$this->host}:{$this->port}" : $this->host,
			'Connection' => 'Close',
			'User-Agent' => 'Mozilla/5.0'
		];
		foreach (['type', 'headers', 'cookies'] as $field) {
			if ($value = $this->_config[$field]) {
				$this->{$field}($value);
			}
		}
		if ($cookies = $this->headers('Cookie')) {
			$this->_parseCookies($cookies);
		}

		$this->_formats += [
			'url' => function($req, $options) {
				$options['port'] = $options['port'] ? ":{$options['port']}" : '';
				$options['path'] = str_replace('//', '/', $options['path']);

				return Text::insert("{:scheme}://{:host}{:port}{:path}{:query}", $options);
			},
			'context' => function($req, $options, $defaults) {
				$req->headers($options['headers']);

				return ['http' => array_diff_key($options, $defaults) + [
					'content' => $req->body(),
					'method' => $options['method'],
					'header' => $req->headers(),
					'protocol_version' => $options['version'],
					'ignore_errors' => $options['ignore_errors'],
					'follow_location' => $options['follow_location'],
					'request_fulluri' => $options['request_fulluri'],
					'proxy' => $options['proxy']
				]];
			},
			'string' => function($req, $options) {
				$body = $req->body();
				$path = str_replace('//', '/', $options['path']) . $options['query'];
				$status = "{$options['method']} {$path} {$req->protocol}";

				return join("\r\n", [$status, join("\r\n", $req->headers()), "", $body]);
			}
		];
	}

	/**
	 * Compile the HTTP message body, optionally encoding its parts according to content type.
	 *
	 * @see lithium\net\http\Message::body()
	 * @see lithium\net\http\Message::_encode()
	 * @param mixed $data
	 * @param array $options
	 *        - `'buffer'` _integer_: split the body string
	 *        - `'encode'` _boolean_: encode the body based on the content type
	 *        - `'decode'` _boolean_: decode the body based on the content type
	 * @return array
	 */
	public function body($data = null, $options = []) {
		$defaults = ['encode' => true];
		return parent::body($data, $options + $defaults);
	}

	/**
	 * Add a cookie to header output, or return a single cookie or full cookie list.
	 *
	 * NOTE: Cookies values are expected to be scalar. This function will not serialize cookie values.
	 * If you wish to store a non-scalar value, you must serialize the data first.
	 *
	 * @param string $key
	 * @param string $value
	 * @return mixed
	 */
	public function cookies($key = null, $value = null) {
		if (is_string($key)) {
			if ($value === null) {
				return isset($this->cookies[$key]) ? $this->cookies[$key] : null;
			}
			if ($value === false) {
				unset($this->cookies[$key]);
				return $this->cookies;
			}
		}
		if ($key) {
			$cookies = is_array($key) ? $key : [$key => $value];
			$this->cookies = $cookies + $this->cookies;
		}
		return $this->cookies;
	}

	/**
	 * Render `Cookie` header, urlencoding invalid characters.
	 *
	 * NOTE: Technically '+' is a valid character, but many browsers erroneously convert these to
	 * spaces, so we must escape this too.
	 *
	 * @return string
	 */
	protected function _cookies() {
		$cookies = $this->cookies;
		$invalid = str_split(",; \+\t\r\n\013\014");
		$replace = array_map('rawurlencode', $invalid);

		foreach ($cookies as $key => &$value) {
			if (!is_scalar($value)) {
				$message = "Non-scalar value cannot be rendered for cookie `{$key}`";
				throw new UnexpectedValueException($message);
			}
			$value = strtr($value, array_combine($invalid, $replace));
			$value = "{$key}={$value}";
		}
		return implode('; ', $cookies);
	}

	/**
	 * Parse `Cookie` header.
	 *
	 * @param string $header `Cookie` header.
	 */
	protected function _parseCookies($header) {
		$cookies = array_map('trim', array_filter(explode('; ', $header)));
		foreach ($cookies as $cookie) {
			list($name, $value) = array_map('urldecode', explode('=', $cookie, 2)) + ['',''];
			$this->cookies($name, $value);
		}
	}

	/**
	 * Get the full query string queryString.
	 *
	 * @param array $params
	 * @param string $format
	 * @return string
	 */
	public function queryString($params = [], $format = null) {
		$result = [];
		$query = [];

		foreach (array_filter([$this->query, $params]) as $querySet) {
			if (is_string($querySet)) {
				$result[] = $querySet;
				continue;
			}
			$query = array_merge($query, $querySet);
		}
		$query = array_filter($query);

		if ($format) {
			$q = null;
			foreach ($query as $key => $value) {
				if (!is_array($value)) {
					$q .= Text::insert($format, [
						'key' => urlencode($key),
						'value' => urlencode($value)
					]);
					continue;
				}
				foreach ($value as $val) {
					$q .= Text::insert($format, [
						'key' => urlencode("{$key}[]"),
						'value' => urlencode($val)
					]);
				}
			}
			$result[] = substr($q, 0, -1);
		} else {
			$result[] = http_build_query($query);
		}

		$result = array_filter($result);
		return $result ? "?" . join("&", $result) : null;
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array. Available
	 * options: array, URL, stream context configuration, or string.
	 *
	 * @see lithium\net\Message::to()
	 * @param string $format Format to convert to. Should be either `'url'`, which returns a string
	 *        representation of the URL that this request points to, or `'context'`, which returns an
	 *        array usable with PHP's `stream_context_create()` function. For more available formats,
	 *        see the parent method, `lithium\net\Message::to()`.
	 * @param array $options Allows overriding of specific portions of the URL, as follows. These
	 *        options should only be specified if you intend to replace the values that are already in
	 *        the `Request` object.
	 *        - `'scheme'` _string_: The protocol scheme of the URL.
	 *        - `'method'` _string_: If applicable, the HTTP method to use in the request. Mainly
	 *                               applies to the `'context'` format.
	 *        - `'host'` _string_: The host name the request is pointing at.
	 *        - `'port'` _string_: The host port, if any.
	 *        - `'path'` _string_: The URL path.
	 *        - `'query'` _mixed_: The query string of the URL as a string or array.
	 *        - `'auth'` _string_: Authentication information. See the constructor for details.
	 *        - `'content'` _string_: The body of the request.
	 *        - `'headers'` _array_: The request headers.
	 *        - `'version'` _string_: The HTTP version of the request, where applicable.
	 * @return mixed Varies; see the `$format` parameter for possible return values.
	 */
	public function to($format, array $options = []) {
		$defaults = [
			'method' => $this->method,
			'scheme' => $this->scheme,
			'host' => $this->host,
			'port' => $this->port,
			'path' => $this->path,
			'query' => null,
			'auth' => $this->auth,
			'username' => $this->username,
			'password' => $this->password,
			'headers' => [],
			'cookies' => [],
			'proxy' => $this->_config['proxy'],
			'body' => null,
			'version' => $this->version,
			'ignore_errors' => $this->_config['ignoreErrors'],
			'follow_location' => $this->_config['followLocation'],
			'request_fulluri' => (boolean) $this->_config['proxy']
		];
		$options += $defaults;

		if (is_string($options['query'])) {
			$options['query'] = "?" . $options['query'];
		} elseif ($options['query']) {
			$options['query'] = "?" . http_build_query($options['query']);
		} elseif ($options['query'] === null) {
			$options['query'] = $this->queryString();
		}

		if ($options['auth']) {
			$data = [];

			if (is_array($options['auth']) && !empty($options['auth']['nonce'])) {
				$data = ['method' => $options['method'], 'uri' => $options['path']];
				$data += $options['auth'];
			}
			$auth = $this->_classes['auth'];
			$data = $auth::encode($options['username'], $options['password'], $data);
			$this->headers('Authorization', $auth::header($data));
		}
		if ($this->cookies($options['cookies'])) {
			$this->headers('Cookie', $this->_cookies());
		}
		$body = $this->body($options['body']);

		if ($body || !in_array($options['method'], ['GET', 'HEAD', 'DELETE'])) {
			$this->headers('Content-Length', strlen($body));
		}

		$conv = isset($this->_formats[$format]) ? $this->_formats[$format] : null;
		return $conv ? $conv($this, $options, $defaults) : parent::to($format, $options);
	}

	/**
	 * Magic method to convert object to string.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->to('string');
	}
}

?>