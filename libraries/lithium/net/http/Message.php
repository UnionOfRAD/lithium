<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\http;

/**
 * Base class for `lithium\net\http\Request` and `lithium\net\http\Response`. Implements basic
 * protocol handling for HTTP-based transactions.
 */
class Message extends \lithium\core\Object {

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

		if (!empty($value)) {
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
	 * Add body parts.
	 *
	 * @param mixed $data
	 * @return array
	 */
	public function body($data = null) {
		$this->body = array_merge((array) $this->body, (array) $data);
		return trim(join("\r\n", $this->body));
	}
}

?>