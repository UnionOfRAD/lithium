<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
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
	 * Key=value pairs after the ?.
	 *
	 * @var array
	 */
	public $query = array();

	/**
	 * The Authorization type: Basic or Digest
	 *
	 * @var string
	 */
	public $auth = null;

	/**
	 * The method of the request, typically one of the following: `GET`, `POST`, `PUT`, `DELETE`,
	 * `OPTIONS`, `HEAD`, `TRACE` or `CONNECT`.
	 *
	 * @var string
	 */
	public $method = 'GET';

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
	 * - `scheme`: http
	 * - `host`: localhost
	 * - `port`: null
	 * - `username`: null
	 * - `password`: null
	 * - `path`: null
	 * - `query`: array - after the question mark ?
	 * - `fragment`: null - after the hashmark #
	 * - `auth` - the Authorization method (Basic|Digest)
	 * - `method` - GET
	 * - `version`: 1.1
	 * - `headers`: array
	 * - `body`: null
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'scheme' => 'http',
			'host' => 'localhost',
			'port' => null,
			'username' => null,
			'password' => null,
			'path' => null,
			'query' => array(),
			'fragment' => null,
			'headers' => array(),
			'body' => null,
			'auth' => null,
			'method' => 'GET'
		);
		$config += $defaults;
		parent::__construct($config);

		$this->headers = array(
			'Host' => $this->port ? "{$this->host}:{$this->port}" : $this->host,
			'Connection' => 'Close',
			'User-Agent' => 'Mozilla/5.0'
		);
		$this->headers($config['headers']);
	}

	/**
	 * Encodes the body based on the type
	 *
	 * @see lithium\net\http\Message::type()
	 * @param mixed $body
	 * @return string
	 */
	protected function _encode($body) {
		$media = $this->_classes['media'];
		if ($type = $media::type($this->_type)) {
			$body = $media::encode($this->_type, $body) ?: $body;
		}
		if (is_array($body)) {
			$body = join("\r\n", $body);
		}
		return $body;
	}

	/**
	 * Add body parts and encodes it into formated string
	 *
	 * @see lithium\net\Message::body()
	 * @param mixed $data
	 * @param array $options
	 *        - `'buffer'`: split the body string
	 * @return array
	 */
	public function body($data = null, $options = array()) {
		$default = array('buffer' => null);
		$options += $default;
		$this->body = array_merge((array) $this->body, (array) $data);

		$body = $this->_encode($this->body);
		return ($options['buffer']) ? str_split($body, $options['buffer']) : $body;
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

		foreach (array_filter(array($this->query, $params)) as $query) {
			if (is_string($query)) {
				$result[] = $query;
				continue;
			}
			$query = array_filter($query);

			if (!$format) {
				$result[] = http_build_query($query);
			}
			if ($format) {
				$q = null;

				foreach ($params as $key => $value) {
					if (is_array($value)) {
						foreach ($value as $val) {
							$q .= String::insert($format, array(
								'key' => urlencode("{$key}[]"),
								'value' => urlencode($val)
							));
						}
						continue;
					}
					$q .= String::insert($format, array(
						'key' => urlencode($key),
						'value' => urlencode($value)
					));
				}
				$result[] = substr($q, 0, -1);
			}

		}
		$result = array_filter($result);
		return (!empty($result)) ? "?" . join("&", $result) : null;
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array. Available
	 * options: array, URL, stream context configuration, or string.
	 *
	 * @see lithium\net\Message::to()
	 * @param string $format Format to convert to. Should be either `'url'`, which returns a string
	 *               representation of the URL that this request points to, or `'context'`, which
	 *               returns an array usable with PHP's `stream_context_create()` function. For
	 *               more available formats, see the parent method, `lithium\net\Message::to()`.
	 * @param array $options Allows overriding of specific portions of the URL, as follows. These
	 *              options should only be specified if you intend to replace the values that are
	 *              already in the `Request` object.
	 *              - `'scheme'` _string_: The protocol scheme of the URL.
	 *              - `'method'` _string_: If applicable, the HTTP method to use in the request.
	 *                Mainly applies to the `'context'` format.
	 *              - `'host'` _string_: The host name the request is pointing at.
	 *              - `'port'` _string_: The host port, if any. If specified, should be prefixed
	 *                with `':'`.
	 *              - `'path'` _string_: The URL path.
	 *              - `'query'` _mixed_: The query string of the URL as a string or array. If passed
	 *                as a string, should be prefixed with `'?'`.
	 *              - `'auth'` _string_: Authentication information. See the constructor for
	 *                details.
	 *              - `'content'` _string_: The body of the request.
	 *              - `'headers'` _array_: The request headers.
	 *              - `'version'` _string_: The HTTP version of the request, where applicable.
	 * @return mixed Varies; see the `$format` parameter for possible return values.
	 */
	public function to($format, array $options = array()) {
		$defaults = array(
			'method' => $this->method,
			'scheme' => $this->scheme,
			'host' => $this->host,
			'port' => $this->port ? ":{$this->port}" : '',
			'path' => $this->path,
			'query' => null,
			'auth' => $this->auth,
			'username' => $this->username,
			'password' => $this->password,
			'headers' => array(),
			'body' => null,
			'version' => $this->version,
			'ignore_errors' => isset($this->_config['ignore_errors'])
				? $this->_config['ignore_errors'] : true,
			'follow_location' => isset($this->_config['follow_location'])
				? $this->_config['follow_location'] : true
		);
		$options += $defaults;

		if (!empty($options['auth'])) {
			$data = array();

			if (is_array($options['auth']) && !empty($options['auth']['nonce'])) {
				$data = array('method' => $options['method'], 'uri' => $options['path']);
				$data += $options['auth'];
			}
			$auth = $this->_classes['auth'];
			$data = $auth::encode($options['username'], $options['password'], $data);
			$this->headers('Authorization', $auth::header($data));
		}
		if (in_array($options['method'], array('POST', 'PUT'))) {
			$media = $this->_classes['media'];
			if ($type = $media::type($this->_type)) {
				$this->headers('Content-Type', $type['content'][0]);
			}
		}

		$body = $this->body($options['body']);
		$this->headers('Content-Length', strlen($body));

		switch ($format) {
			case 'url':
				$options['query'] = $this->queryString($options['query']);
				$options['path'] = str_replace('//', '/', $options['path']);
				return String::insert("{:scheme}://{:host}{:port}{:path}{:query}", $options);
			case 'context':
				$base = array(
					'content' => $body,
					'method' => $options['method'],
					'header' => $this->headers($options['headers']),
					'protocol_version' => $options['version'],
					'ignore_errors' => $options['ignore_errors'],
					'follow_location' => $options['follow_location']
				);
				return array('http' => array_diff_key($options, $defaults) + $base);
			case 'string':
				$path = str_replace('//', '/', $this->path) . $this->queryString($options['query']);
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