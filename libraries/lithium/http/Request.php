<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
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
	 * {{{
	 *     array(
	 *          'Host' => $this->host . ":" . $this->port,
	 *          'Connection' => 'Close', 'User-Agent' => 'Mozilla/5.0 (Lithium)'
	 *     )
	 * }}}
	 * @var array
	 */
	public $headers = array();

	/**
	 * The authentication/authorization information
	 * {{{
	 *     array('method' => 'Basic', 'username' => 'lithium', 'password' => 'rad')
	 * }}}
	 * @var array
	 */
	public $auth = array();

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
		$defaults = array(
			'host' => 'localhost', 'port' => 80, 'method' => 'GET', 'path' => '/',
			'headers' => array(), 'body' => array(), 'params' => array()
		);
		$config += $defaults;
		foreach ($config as $key => $value) {
			$this->{$key} = $value;
		}

		$this->protocol = "HTTP/{$this->version}";

		$this->headers = array(
			'Host' => $this->host . ":" . $this->port,
			'Connection' => 'Close', 'User-Agent' => 'Mozilla/5.0 (Lithium)'
		);
		$this->headers($config['headers']);

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

	public function to($type = 'string') {
		if ($type == 'array') {
			return array(
				'method' => $this->method,
				'content' => $this->body(),
				'header' => $this->headers()
			);
		}
		return (string) $this;
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