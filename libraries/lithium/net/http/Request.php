<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
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
	 * @return object
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
			'User-Agent' => 'Mozilla/5.0',
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
	 * options: array, url, context, or string.
	 *
	 * @param string $format Format to convert to.
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, array $options = array()) {
		switch ($format) {
			case 'url':
				$query = $this->queryString();
				$host = $this->host . ($this->port ? ":{$this->port}" : '');
				return "{$this->scheme}://{$host}{$this->path}{$query}";
			case 'context':
				if ($this->_config['auth']) {
					$auth = base64_encode("{$this->username}:{$this->password}");
					$this->headers('Authorization', "{$this->_config['auth']} {$auth}");
				}
				$content = $this->body();
				$this->headers('Content-Length', strlen($content));
				$defaults = array(
					'method' => $this->method,
					'header' => $this->headers(), 'content' => $content,
					'protocol_version' => $this->version, 'ignore_errors' => true
				);
				return array('http' => $options + $defaults);
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