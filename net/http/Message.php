<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\http;

/**
 * Base class for `lithium\net\http\Request` and `lithium\net\http\Response`. Implements basic
 * protocol handling for HTTP-based transactions.
 */
class Message extends \lithium\net\Message {

	/**
	 * The full protocol: HTTP/1.1
	 *
	 * @var string
	 */
	public $protocol = null;

	/**
	 * Specification version number
	 *
	 * @var string
	 */
	public $version = '1.1';

	/**
	 * headers
	 *
	 * @var array
	 */
	public $headers = array();

	/**
	 * Content-Type
	 *
	 * @var string
	 */
	protected $_type = 'html';

	/**
	 * Classes used by `Request`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'media' => 'lithium\net\http\Media',
		'auth' => 'lithium\net\http\Auth'
	);

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
			'protocol' => null,
			'version' => '1.1',
			'headers' => array(),
			'body' => null
		);
		$config += $defaults;
		parent::__construct($config);

		if (strpos($this->host, '/') !== false) {
			list($this->host, $this->path) = explode('/', $this->host, 2);
		}
		$this->path = str_replace('//', '/', "/{$this->path}");
		$this->protocol = $this->protocol ?: "HTTP/{$this->version}";
	}

	/**
	 * Add a header to rendered output, or return a single header or full header list.
	 *
	 * @param string $key
	 * @param string $value
	 * @return array
	 */
	public function headers($key = null, $value = null) {
		if (is_string($key) && strpos($key, ':') === false) {
			if ($value === null) {
				return isset($this->headers[$key]) ? $this->headers[$key] : null;
			}
			if ($value === false) {
				unset($this->headers[$key]);
				return $this->headers;
			}
		}

		foreach (($value ? array($key => $value) : (array) $key) as $header => $value) {
			if (!is_string($header)) {
				if (preg_match('/(.*?):(.+)/', $value, $match)) {
					$this->headers[$match[1]] = trim($match[2]);
				}
			} else {
				$this->headers[$header] = $value;
			}
		}
		$headers = array();

		foreach ($this->headers as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $val) {
					$headers[] = "{$key}: {$val}";
				}
				continue;
			}
			$headers[] = "{$key}: {$value}";
		}
		return $headers;
	}

	/**
	 * Sets/Gets the content type
	 *
	 * @param string $type a full content type i.e. `'application/json'` or simple name `'json'`
	 * @return string A simple content type name, i.e. `'html'`, `'xml'`, `'json'`, etc., depending
	 *         on the content type of the request.
	 */
	public function type($type = null) {
		if ($type == null && $type !== false) {
			return $this->_type;
		}
		if (strpos($type, '/')) {
			$media = $this->_classes['media'];

			if (!$data = $media::type($type)) {
				return $this->_type;
			}
			$type = is_array($data) ? reset($data) : $data;
		}
		return $this->_type = $type;
	}
}

?>