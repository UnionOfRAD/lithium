<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\http;

use lithium\util\String;

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
	public $query = array();

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
	public $cookies = array();

	/**
	 * Adds config values to the public properties when a new object is created.
	 *
	 * @param array $config Configuration options : default value
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
	 *        - `'proxy'` _string_: null
	 *        - `'ignoreErrors'` _boolean_: true
	 *        - `'followLocation'` _boolean_: true
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'method' => 'GET',
			'query' => array(),
			'type' => null,
			'auth' => null,
			'proxy' => null,
			'ignoreErrors' => true,
			'followLocation' => true
		);
		$config += $defaults;

		$this->method  = $config['method'];
		$this->query   = $config['query'];
		$this->auth    = $config['auth'];
		parent::__construct($config);

		$this->headers = array(
			'Host' => $this->port ? "{$this->host}:{$this->port}" : $this->host,
			'Connection' => 'Close',
			'User-Agent' => 'Mozilla/5.0'
		);
		if ($type = $this->_config['type']) {
			$this->type($type);
		}
		$this->headers($this->_config['headers']);
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
	public function body($data = null, $options = array()) {
		$defaults = array('encode' => true);
		return parent::body($data, $options + $defaults);
	}

	/**
	 * Get the full query string queryString.
	 *
	 * @param array $params
	 * @param string $format
	 * @return string
	 */
	public function queryString($params = array(), $format = null) {
		$result = array();
		$query = array();

		foreach (array_filter(array($this->query, $params)) as $querySet) {
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
					$q .= String::insert($format, array(
						'key' => urlencode($key),
						'value' => urlencode($value)
					));
					continue;
				}
				foreach ($value as $val) {
					$q .= String::insert($format, array(
						'key' => urlencode("{$key}[]"),
						'value' => urlencode($val)
					));
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
	public function to($format, array $options = array()) {
		$defaults = array(
			'method' => $this->method,
			'scheme' => $this->scheme,
			'host' => $this->host,
			'port' => $this->port,
			'path' => $this->path,
			'query' => null,
			'auth' => $this->auth,
			'username' => $this->username,
			'password' => $this->password,
			'headers' => array(),
			'proxy' => $this->_config['proxy'],
			'body' => null,
			'version' => $this->version,
			'ignore_errors' => $this->_config['ignoreErrors'],
			'follow_location' => $this->_config['followLocation'],
			'request_fulluri' => (boolean) $this->_config['proxy']
		);
		$options += $defaults;

		if (is_string($options['query'])) {
			$options['query'] = "?" . $options['query'];
		} elseif ($options['query']) {
			$options['query'] = "?" . http_build_query($options['query']);
		} elseif ($options['query'] === null) {
			$options['query'] = $this->queryString();
		}

		if ($options['auth']) {
			$data = array();

			if (is_array($options['auth']) && !empty($options['auth']['nonce'])) {
				$data = array('method' => $options['method'], 'uri' => $options['path']);
				$data += $options['auth'];
			}
			$auth = $this->_classes['auth'];
			$data = $auth::encode($options['username'], $options['password'], $data);
			$this->headers('Authorization', $auth::header($data));
		}
		$body = $this->body($options['body']);
		$this->headers('Content-Length', strlen($body));

		switch ($format) {
			case 'url':
				$options['port'] = $options['port'] ? ":{$options['port']}" : '';
				$options['path'] = str_replace('//', '/', $options['path']);
				return String::insert("{:scheme}://{:host}{:port}{:path}{:query}", $options);
			case 'context':
				$base = array(
					'content' => $body,
					'method' => $options['method'],
					'header' => $this->headers($options['headers']),
					'protocol_version' => $options['version'],
					'ignore_errors' => $options['ignore_errors'],
					'follow_location' => $options['follow_location'],
					'request_fulluri' => $options['request_fulluri'],
					'proxy' => $options['proxy']
				);
				return array('http' => array_diff_key($options, $defaults) + $base);
			case 'string':
				$path = str_replace('//', '/', $this->path) . $options['query'];
				$status = "{$this->method} {$path} {$this->protocol}";
				return join("\r\n", array($status, join("\r\n", $this->headers()), "", $body));
			default:
				return parent::to($format, $options);
		}
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