<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
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
	 * Used to build query string.
	 *
	 * @var array
	 */
	public $params = array();

	/**
	 * Headers.
	 *
	 * For example:
	 * {{{
	 * 	array(
	 * 		'Host' => $this->host . ":" . $this->port,
	 * 		'Connection' => 'Close', 'User-Agent' => 'Mozilla/5.0'
	 * 	)
	 * }}}
	 * @var array
	 */
	public $headers = array();

	/**
	 * Cookies.
	 *
	 * @var array
	 */
	public $cookies = array();

	/**
	 * Constructor
	 *
	 * @param array $config
	 *        - auth: the Authorization method (Basic|Digest)
	 *        - username: the username for auth
	 *        - password: the password for auth
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'scheme' => 'http',
			'host' => 'localhost',
			'port' => null,
			'method' => 'GET',
			'path' => '/',
			'auth' => null,
			'headers' => array(),
			'body' => array(),
			'params' => array()
		);
		$config += $defaults;
		parent::__construct($config);

		$this->protocol = "HTTP/{$this->version}";
		$this->headers = array(
			'Host' => $this->port ? "{$this->host}:{$this->port}" : $this->host,
			'Connection' => 'Close',
			'User-Agent' => 'Mozilla/5.0'
		);
		$this->headers($config['headers']);

		if (strpos($this->host, '/') !== false) {
			$parts = explode('/', $this->host, 2);
			$this->host = $parts[0];
			$this->path = str_replace('//', '/', "/{$parts[1]}/");
		}
	}

	/**
	 * Set queryString.
	 *
	 * @param array $params
	 * @param string $format
	 * @return array
	 */
	public function queryString($params = array(), $format = "{:key}={:value}&") {
		if (!$params) {
			if (is_string($this->params)) {
				return "?" . $this->params;
			}
			$params = $this->params;
		} elseif (is_array($this->params)) {
			$params = array_merge($this->params, $params);
		}
		$query = null;

		foreach ($params as $key => $value) {
			$values = array('key' => urlencode($key), 'value' => urlencode($value));
			$query .= String::insert($format, $values);
		}
		if (!$query) {
			return null;
		}
		return "?" . $this->params = substr($query, 0, -1);
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
			'query' => $this->queryString(),
			'auth' => $this->_config['auth'],
			'content' => $this->body(),
			'version' => $this->version,
		);
		$options += $defaults;

		if ($options['query'] && is_array($options['query'])) {
			$options['query'] = $this->queryString($options['query']);
		}

		switch ($format) {
			case 'url':
				return String::insert("{:scheme}://{:host}{:port}{:path}{:query}", $options);
			case 'context':
				if ($options['auth']) {
					$auth = base64_encode("{$this->username}:{$this->password}");
					$this->headers('Authorization', "{$options['auth']} {$auth}");
				}
				$this->headers('Content-Length', strlen($options['content']));
				$base = array(
					'content' => $options['content'],
					'method' => $options['method'],
					'header' => $this->headers(),
					'protocol_version' => $options['version'],
					'ignore_errors' => true
				);
				return array('http' => array_diff_key($options, $defaults) + $base);
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
		if (!empty($this->_config['auth'])) {
			$this->headers('Authorization', "{$this->_config['auth']} " . base64_encode(
				"{$this->username}:{$this->password}"
			));
		}
		$path = str_replace('//', '/', $this->path) . $this->queryString();
		$body = $this->body();
		$this->headers('Content-Length', strlen($body));

		$status = "{$this->method} {$path} {$this->protocol}";
		return join("\r\n", array($status, join("\r\n", $this->headers()), "", $body));
	}
}

?>