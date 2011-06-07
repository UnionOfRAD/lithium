<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
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
	public $protocol = 'HTTP/1.1';

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
	 * body
	 *
	 * @var array
	 */
	public $body = array();

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
	protected $_classes = array('media' => 'lithium\net\http\Media');

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

		if ($value) {
			$this->headers = array_merge($this->headers, array($key => $value));
		} else {
			foreach ((array) $key as $header => $value) {
				if (!is_string($header)) {
					if (preg_match('/(.*?):(.+)/i', $value, $match)) {
						$this->headers[$match[1]] = trim($match[2]);
					}
				} else {
					$this->headers[$header] = $value;
				}
			}
		}
		$headers = array();

		foreach ($this->headers as $key => $value) {
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
		if ($type === null) {
			return $this->_type;
		}
		if (strpos($type, '/')) {
			$media = $this->_classes['media'];

			if ($data = $media::type($type)) {
				$type = is_array($data) ? reset($data) : $data;
			}
		}
		return $this->_type = $type;
	}
}

?>