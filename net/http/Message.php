<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2014, Union of RAD (http://union-of-rad.org)
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
	 * Adds, gets or removes one or multiple headers at the same time.
	 *
	 * Header names are not normalized and their casing left untouched. When
	 * headers are retrieved no sorting takes place. This behavior is inline
	 * with the specification which states header names should be treated in
	 * a case-insensitive way. Sorting is suggested but not required.
	 *
	 * {{{
	 * // Get single or multiple headers.
	 * $request->headers('Content-Type'); // returns 'text/plain'
	 * $request->headers(); // returns array('Content-Type: text/plain', ... )
	 *
	 * // Set single or multiple headers.
	 * $request->headers('Content-Type', 'text/plain');
	 * $request->headers(array('Content-Type' => 'text/plain', ...));
	 *
	 * // Alternatively use full header line.
	 * $request->headers('Content-Type: text/plain');
	 * $request->headers(array('Content-Type: text/plain', ...));
	 *
	 * // Removing single or multiple headers.
	 * $request->headers('Content-Type', false);
	 * $request->headers(array('Content-Type' => false, ...));
	 * }}}
	 *
	 * Certain header fields support multiple values. These can be separated by
	 * comma or alternatively the header repeated for each value in the list.
	 *
	 * When explicitly adding a value to an already existing header (that is when
	 * $replace is `false`) an array with those values is kept/created internally.
	 * Later when retrieving headers the header will be repeated for each value.
	 *
	 * Note: Multiple headers of the same name are only valid if the values of
	 * that header can be separated by comma as defined in section 4.2 of RFC2616.
	 *
	 * {{{
	 * // Replace single or multiple headers
	 * $request->headers('Cache-Control', 'no-store');
	 * $request->headers(array('Cache-Control' => 'public'));
	 * $request->headers('Cache-Control'); // returns 'public'
	 *
	 * // Merging with existing array headers.
	 * // Note that new elements are just appended and no sorting takes place.
	 * $request->headers('Cache-Control', 'no-store');
	 * $request->headers('Cache-Control', 'no-cache', false);
	 * $request->headers();
	 * // returns array('Cache-Control: no-store', 'Cache-Control: no-cache')
	 *
	 * $request->headers('Cache-Control', 'no-store');
	 * $request->headers('Cache-Control', array('no-cache'), false);
	 * $request->headers();
	 * // returns array('Cache-Control: no-store', 'Cache-Control: no-cache')
	 *
	 * $request->headers('Cache-Control', 'max-age=0');
	 * $request->headers('Cache-Control', 'no-store, no-cache');
	 * $request->headers();
	 * // returns array('Cache-Control: max-age=0', 'Cache-Control: no-store, no-cache')
	 * }}}
	 *
	 * @link http://www.ietf.org/rfc/rfc2616.txt Section 4.2 Message Headers
	 * @param string|array $key A header name, a full header line (`'<key>: <value>'`), or an array
	 *                      of headers to set in `key => value` form.
	 * @param string|null|boolean $value A value to set if `$key` is a string. If `null`, returns
	 *                            the value of the header corresponding to `$key`. If `false`,
	 *                            it unsets the header corresponding to `$key`.
	 * @param boolean $replace Whether to override or add alongside any existing header with
	 *                the same name.
	 * @return string|array When called with just $key provided, the value of a single header. When
	 *         calling the method without any arguments, an array of compiled headers in the
	 *         form `array('<key>: <value>', ...)` is returned. For convience the latter also
	 *         happens when setting one or multiple headers.
	 */
	public function headers($key = null, $value = null, $replace = true) {
		if (!is_string($key)) {
			$replace = ($value === false) ? $value : $replace;

			foreach ((array) $key as $header => $value) {
				if (is_string($header)) {
					$this->headers($header, $value, $replace);
					continue;
				}
				$this->headers($value, null, $replace);
			}
		} elseif ($key) {
			if (strpos($key, ':') !== false) {
				if (preg_match('/(.*?):(.+)/', $key, $match)) {
					$this->headers($match[1], trim($match[2]), $replace);
				}
			} elseif ($value === null) {
				return isset($this->headers[$key]) ? $this->headers[$key] : null;
			} elseif ($value === false) {
				unset($this->headers[$key]);
			} elseif (!$replace && isset($this->headers[$key]) && $value != $this->headers[$key]) {
				$this->headers[$key] = (array) $this->headers[$key];

				if (is_string($value)) {
					$this->headers[$key][] = $value;
				} else {
					$this->headers[$key] = array_merge($this->headers[$key], $value);
				}
			} else {
				$this->headers[$key] = $value;
			}
		}
		$headers = array();

		foreach ($this->headers as $key => $value) {
			if (is_scalar($value)) {
				$headers[] = "{$key}: {$value}";
				continue;
			}
			foreach ($value as $val) {
				$headers[] = "{$key}: {$val}";
			}
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
			$header = is_string($data['content']) ? $data['content'] : reset($data['content']);
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

		if (!empty($data)) {
			$this->body = array_merge((array) $this->body, (array) $data);
		}
		$body = $this->body;

		if (empty($options['buffer']) && empty($body)) {
			return "";
		}
		if ($options['encode']) {
			$body = $this->_encode($body);
		}
		$body = is_string($body) ? $body : join("\r\n", (array) $body);

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