<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\http;

use \lithium\util\String;

/**
 * Facilitates HTTP request creation by assembling connection and path info, `GET` and `POST` data,
 * and authentication credentials in a single, stateful object.
 */
class Request extends \lithium\net\http\Message {

	/**
	 * The protocol scheme to be used in the request. Used when calculating the target URL of this
	 * request's end point.
	 *
	 * @var string
	 */
	public $scheme = 'http';

	/**
	 * The Host header value and authority.
	 *
	 * @var string
	 */
	public $host = 'localhost';

	/**
	 * Port number.
	 *
	 * @var string
	 */
	public $port = 80;

	/**
	 * The method of the request, typically one of the following: `GET`, `POST`, `PUT`, `DELETE`,
	 * `OPTIONS`, `HEAD`, `TRACE` or `CONNECT`.
	 *
	 * @var string
	 */
	public $method = 'GET';

	/**
	 * Absolute path of the request.
	 *
	 * @var string
	 */
	public $path = '/';

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
	 * The authentication/authorization information
	 *
	 * For example:
	 * {{{
	 *     array('method' => 'Basic', 'username' => 'lithium', 'password' => 'rad')
	 * }}}
	 * @var array
	 */
	public $auth = array();

	/**
	 * Cookies.
	 *
	 * @var array
	 */
	public $cookies = array();

	/**
	 * Body.
	 *
	 * @var array
	 */
	public $body = array();

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @return object
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'scheme' => 'http',
			'host' => 'localhost',
			'port' => 80,
			'method' => 'GET',
			'path' => '/',
			'headers' => array(),
			'body' => array(),
			'params' => array()
		);
		$config += $defaults;

		foreach ($config as $key => $value) {
			$this->{$key} = $value;
		}
		$this->protocol = "HTTP/{$this->version}";

		$this->headers = array(
			'Host' => "{$this->host}:{$this->port}",
			'Connection' => 'Close',
			'User-Agent' => 'Mozilla/5.0',
		);
		$this->headers($config['headers']);

		if (!empty($config['auth']['password'])) {
			$this->headers('Authorization', $config['auth']['method'] . ' ' . base64_encode(
				$config['auth']['username'] . ':' . $config['auth']['password']
			));
		}
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
		if (empty($params)) {
			if (is_string($this->params)) {
				return "?" . $this->params;
			}
			$params = $this->params;
		} elseif (is_array($this->params)) {
			$params = array_merge($this->params, $params);
		}
		$query = null;

		foreach ($params as $key => $value) {
			$query .= String::insert($format, array(
				'key' => urlencode($key), 'value' => urlencode($value)
			));
		}
		if (empty($query)) {
			return null;
		}
		return "?" . $this->params = substr($query, 0, -1);
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array. Available
	 * options: array, url, context, or string.
	 *
	 * @param string $format Format to convert to.
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, array $options = array()) {
		switch ($format) {
			case 'array':
				$method = $this->method;
				$content = $this->body();
				$header = $this->headers();
				return compact('method', 'content', 'header');
			case 'url':
				$query = $this->queryString();
				return "{$this->scheme}://{$this->host}:{$this->port}{$this->path}{$query}";
			case 'context':
				return array($this->scheme => $options + $this->to('array'));
			case 'string':
			default:
				return (string) $this;
		}
	}

	/**
	 * Magic method to convert object to string.
	 *
	 * @return string
	 */
	public function __toString() {
		$query = $this->queryString();
		$path = str_replace('//', '/', $this->path) . $query;

		$body = $this->body();
		$this->headers('Content-Length', strlen($body));

		$request = array(
			"{$this->method} {$path} {$this->protocol}",
			join("\r\n", $this->headers()),
			"", $body
		);
		return join("\r\n", $request);
	}
}

?>