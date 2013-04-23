<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
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
	 * HTTP headers
	 *
	 * @var array
	 */
	public $headers = array();

	/**
	 * Content-Type
	 *
	 * @var string
	 */
	protected $_type = null;

	/**
	 * Classes used by `Message` and its subclasses.
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
	 *        - `'protocol'` _string_: null
	 *        - `'version'` _string_: '1.1'
	 *        - `'scheme'` _string_: 'http'
	 *        - `'host'` _string_: 'localhost'
	 *        - `'port'` _integer_: null
	 *        - `'username'` _string_: null
	 *        - `'password'` _string_: null
	 *        - `'path'` _string_: null
	 *        - `'headers'` _array_: array()
	 *        - `'body'` _mixed_: null
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'protocol' => null,
			'version' => '1.1',
			'scheme' => 'http',
			'host' => 'localhost',
			'headers' => array()
		);
		$config += $defaults;

		foreach (array_intersect_key(array_filter($config), $defaults) as $key => $value) {
			$this->{$key} = $value;
		}
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
	 * @param string $key A header name, a full header line (`'Key: Value'`), or an array of headers
	 *        to set in `key => value` form.
	 * @param string $value A value to set if `$key` is a string. If `null`, returns the value of the
	 *        header corresponding to `$key`. If `false`, it unsets the header corresponding to `$key`.
	 * @param boolean $replace Whether to override or add alongside any existing header with the same
	 *        name.
	 * @return mixed The value of a single header, or an array of compiled headers in the form
	 *         `'Key: Value'`.
	 */
	public function headers($key = null, $value = null, $replace = true) {
		if (!is_string($key) || strpos($key, ':') !== false) {
			$replace = ($value === false) ? $value : $replace;

			foreach ((array) $key as $header => $value) {
				if (!is_string($header)) {
					if (preg_match('/(.*?):(.+)/', $value, $match)) {
						$this->headers($match[1], trim($match[2]), $replace);
					}
				} else {
					$this->headers($header, $value, $replace);
				}
			}
		} else {
			if ($value === null) {
				return isset($this->headers[$key]) ? $this->headers[$key] : null;
			}
			if ($value === false) {
				unset($this->headers[$key]);
			}
			elseif (!$replace && isset($this->headers[$key])) {
				$this->headers[$key] = (array) $this->headers[$key];
				if (is_array($value)) {
					$this->headers[$key] = array_merge($this->headers[$key], $value);
				} else {
					$this->headers[$key][] = $value;
				}
			} else {
				$this->headers[$key] = $value;
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
	 * Sets/gets the content type.
	 *
	 * @param string $type A full content type i.e. `'application/json'` or simple name `'json'`
	 * @return string A simple content type name, i.e. `'html'`, `'xml'`, `'json'`, etc., depending
	 *         on the content type of the request.
	 */
	public function type($type = null) {
		if ($type === false) {
			unset($this->headers['Content-Type']);
			$this->_type = false;
			return;
		}
		$media = $this->_classes['media'];

		if (!$type && $this->_type) {
			return $this->_type;
		}
		$headers = $this->headers + array('Content-Type' => null);
		$type = $type ?: $headers['Content-Type'];

		if (!$type) {
			return;
		}
		$header = $type;

		if (!$data = $media::type($type)) {
			$this->headers('Content-Type', $type);
			return ($this->_type = $type);
		}
		if (is_string($data)) {
			$type = $data;
		} elseif (!empty($data['content'])) {
			$header = is_array($data['content']) ? reset($data['content']) : $data['content'];
		}
		$this->headers('Content-Type', $header);
		return ($this->_type = $type);
	}

	/**
	 * Add data to and compile the HTTP message body, optionally encoding or decoding its parts
	 * according to content type.
	 *
	 * @param mixed $data
	 * @param array $options
	 *        - `'buffer'` _integer_: split the body string
	 *        - `'encode'` _boolean_: encode the body based on the content type
	 *        - `'decode'` _boolean_: decode the body based on the content type
	 * @return array
	 */
	public function body($data = null, $options = array()) {
		$default = array('buffer' => null, 'encode' => false, 'decode' => false);
		$options += $default;
		$body = $this->body = array_filter(array_merge((array) $this->body, (array) $data));

		if (empty($options['buffer']) && empty($body)) {
			return "";
		}
		if ($options['encode']) {
			$body = $this->_encode($body);
		}
		$body = is_array($body) ? join("\r\n", $body) : $body;

		if ($options['decode']) {
			$body = $this->_decode($body);
		}
		return ($options['buffer']) ? str_split($body, $options['buffer']) : $body;
	}

	/**
	 * Encode the body based on the content type
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
		return $body;
	}

	/**
	 * Decode the body based on the content type
	 *
	 * @see lithium\net\http\Message::type()
	 * @param string $body
	 * @return mixed
	 */
	protected function _decode($body) {
		$media = $this->_classes['media'];

		if ($type = $media::type($this->_type)) {
			return $media::decode($this->_type, $body) ?: $body;
		}
		return $body;
	}
}

?>