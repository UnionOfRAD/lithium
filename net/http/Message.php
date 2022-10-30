<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
	public $headers = [];

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
	protected $_classes = [
		'media' => 'lithium\net\http\Media',
		'auth' => 'lithium\net\http\Auth'
	];

	/**
	 * Constructor. Adds config values to the public properties when a new object is created.
	 *
	 * @see lithium\net\Message::__construct()
	 * @param array $config The available configuration options are the following. Further
	 *        options are inherited from the parent class.
	 *        - `'protocol'` _string_: Defaults to `null`.
	 *        - `'version'` _string_: Defaults to `'1.1'`.
	 *        - `'scheme'` _string_: Overridden and defaulting to `'http'`.
	 *        - `'headers'` _array_: Defaults to `[]`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'protocol' => null,
			'version' => '1.1',
			'scheme' => 'http',
			'headers' => []
		];
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
	 * ```
	 * // Get single or multiple headers.
	 * $request->headers('Content-Type'); // returns 'text/plain'
	 * $request->headers(); // returns ['Content-Type: text/plain', ... ]
	 *
	 * // Set single or multiple headers.
	 * $request->headers('Content-Type', 'text/plain');
	 * $request->headers(['Content-Type' => 'text/plain', ...]);
	 *
	 * // Alternatively use full header line.
	 * $request->headers('Content-Type: text/plain');
	 * $request->headers(['Content-Type: text/plain', ...]);
	 *
	 * // Removing single or multiple headers.
	 * $request->headers('Content-Type', false);
	 * $request->headers(['Content-Type' => false, ...]);
	 * ```
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
	 * ```
	 * // Replace single or multiple headers
	 * $request->headers('Cache-Control', 'no-store');
	 * $request->headers(['Cache-Control' => 'public']);
	 * $request->headers('Cache-Control'); // returns 'public'
	 *
	 * // Merging with existing array headers.
	 * // Note that new elements are just appended and no sorting takes place.
	 * $request->headers('Cache-Control', 'no-store');
	 * $request->headers('Cache-Control', 'no-cache', false);
	 * $request->headers();
	 * // returns ['Cache-Control: no-store', 'Cache-Control: no-cache']
	 *
	 * $request->headers('Cache-Control', 'no-store');
	 * $request->headers('Cache-Control', ['no-cache'], false);
	 * $request->headers();
	 * // returns ['Cache-Control: no-store', 'Cache-Control: no-cache']
	 *
	 * $request->headers('Cache-Control', 'max-age=0');
	 * $request->headers('Cache-Control', 'no-store, no-cache');
	 * $request->headers();
	 * // returns ['Cache-Control: no-store, no-cache']
	 * ```
	 *
	 * @link http://www.ietf.org/rfc/rfc2616.txt Section 4.2 Message Headers
	 * @param string|array $key A header name, a full header line (`'<key>: <value>'`), or an array
	 *                      of headers to set in `key => value` form.
	 * @param mixed $value A value to set if `$key` is a string.
	 *              It can be an array to set multiple headers with the same key.
	 *              If `null`, returns the value of the header corresponding to `$key`.
	 *              If `false`, it unsets the header corresponding to `$key`.
	 * @param boolean $replace Whether to override or add alongside any existing header with
	 *                the same name.
	 * @return mixed When called with just $key provided, the value of a single header or an array
	 *         of values in case there is multiple headers with this key.
	 *         When calling the method without any arguments, an array of compiled headers in the
	 *         form `['<key>: <value>', ...]` is returned. All set and replace operations
	 *         return no value for performance reasons.
	 */
	public function headers($key = null, $value = null, $replace = true) {
		if ($key === null && $value === null) {
			$headers = [];

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
		if ($value === null && is_string($key) && strpos($key, ':') === false) {
			return isset($this->headers[$key]) ? $this->headers[$key] : null;
		}

		if (is_string($key)) {
			if (strpos($key, ':') !== false && preg_match('/(.*?):(.+)/', $key, $match)) {
				$key = $match[1];
				$value = trim($match[2]);
			} elseif ($value === false) {
				unset($this->headers[$key]);
				return;
			}
			if ($replace || !isset($this->headers[$key])) {
				$this->headers[$key] = $value;
			} elseif ($value !== $this->headers[$key]) {
				$this->headers[$key] = (array) $this->headers[$key];

				if (is_string($value)) {
					$this->headers[$key][] = $value;
				} else {
					$this->headers[$key] = array_merge($this->headers[$key], $value);
				}
			}
		} else {
			$replace = ($value === false) ? $value : $replace;

			foreach ((array) $key as $header => $value) {
				if (is_string($header)) {
					$this->headers($header, $value, $replace);
					continue;
				}
				$this->headers($value, null, $replace);
			}
		}
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
		$headers = $this->headers + ['Content-Type' => null];
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
	public function body($data = null, $options = []) {
		$default = ['buffer' => null, 'encode' => false, 'decode' => false];
		$options += $default;

		if ($data !== null) {
			$this->body = array_merge((array) $this->body, (array) $data);
		}
		$body = $this->body;

		if (empty($options['buffer']) && $body === null) {
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

		if ($media::type($this->_type)) {
			$encoded = $media::encode($this->_type, $body);
			$body = $encoded !== null ? $encoded : $body;
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

		if ($media::type($this->_type)) {
			$decoded = $media::decode($this->_type, $body);
			$body = $decoded !== null ? $decoded : $body;
		}
		return $body;
	}
}

?>