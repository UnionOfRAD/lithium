<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\http;

use \lithium\util\String;

/**
 * Facilitates HTTP request creation by assembling connection and path info, `GET` and `POST` data,
 * and authentication credentials in a single, stateful object.
 */
class Request extends \lithium\http\Base {

	/**
	 * The Host header value and authority
	 *
	 * @var string
	 */
	public $host = 'localhost';

	/**
	 * Port number
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
	 * absolute path of the request
	 *
	 * @var string
	 */
	public $path = '/';

	/**
	 * Used to build query string
	 *
	 * @var array
	 */
	public $params = array();

	/**
	 * headers
	 *
	 * @var array
	 */
	public $headers = array(
		'Host' => 'localhost:80',
		'Connection' => 'Close',
		'User-Agent' => 'Mozilla/5.0 (Lithium)'
	);

	/**
	 * The authentication/authorization information
	 *
	 * @var array
	 */
	public $auth = array(
	/*	'method' => 'Basic',
		'username' => null,
		'password' => null,
	*/
	);

	/**
	 * cookies
	 *
	 * @var array
	 */
	public $cookies = array();

	/**
	 * body
	 *
	 * @var array
	 */
	public $body = array();

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$this->build($config);
	}

	/**
	 * Build the request object
	 *
	 * @param string $config
	 * @return void
	 */
	public function build($config) {
		foreach ($config as $key => $value) {
			if (isset($this->{$key}) && !is_array($this->{$key})) {
				$this->{$key} = $value;
			}
		}
		$this->protocol = "HTTP/{$this->version}";

		$this->headers('Host', $this->host . ":" . $this->port);

		if (!empty($config['headers'])) {
			$this->headers($config['headers']);
		}
		if (!empty($config['body'])) {
			$this->body($config['body']);
		}
		if (!empty($config['params'])) {
			$this->params = $config['params'];
		}
		if (!empty($config['auth']['password'])) {
			$this->headers('Authorization',
				$config['auth']['method'] . ' '
				. base64_encode(
					$config['auth']['username'] . ':'
					. $config['auth']['password']
				)
			);
		}
	}

	/**
	 * Set queryString
	 *
	 * @return array
	 */
	public function queryString($params = array(), $format = "{:key}={:value}&") {
		$query = null;
		if (is_string($params)) {
			list($format, $params) = func_get_args();
		}
		$params = array_merge((array)$this->params, $params);
		foreach ((array) $params as $key => $value) {
			$query .= String::insert($format, array(
				'key' => urlencode($key), 'value' => urlencode($value)
			));
		}
		if (empty($query)) {
			return null;
		}
		return "?" . substr($query, 0, -1);
	}

	/**
	 * magic method to convert object to string
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