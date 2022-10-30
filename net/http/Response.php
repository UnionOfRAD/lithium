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
 * Parses and stores the status, headers and body of an HTTP response.
 */
class Response extends \lithium\net\http\Message {

	/**
	 * Status code and message.
	 *
	 * @var array
	 */
	public $status = ['code' => 200, 'message' => 'OK'];

	/**
	 * Character encoding.
	 *
	 * @var string
	 */
	public $encoding = 'UTF-8';

	/**
	 * Cookies to be set in this HTTP response, usually parsed from `Set-Cookie` headers.
	 *
	 * Cookies are stored as arrays of `$name => $value` where `$value` is an associative array
	 * or an array of associative arrays which contain at minimum a `value` key and optionally,
	 * `expire`, `path`, `domain`, `secure`, and/or `httponly` keys corresponding to the parameters
	 * of PHP `setcookie()`.
	 *
	 * @see lithium\net\http\Response::cookies()
	 * @link http://php.net/function.setcookie.php
	 * @var array
	 */
	public $cookies = [];

	/**
	 * Status codes.
	 *
	 * @var array
	 */
	protected $_statuses = [
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Time-out',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Method Failure',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out',
		507 => 'Insufficient Storage',
		511 => 'Network Authentication Required'
	];

	/**
	 * Constructor. Adds config values to the public properties when a new object is created.
	 *
	 * @see lithium\net\http\Message::__construct()
	 * @see lithium\net\Message::__construct()
	 * @param array $config The available configuration options are the following. Further
	 *        options are inherited from the parent classes.
	 *        - `'message'` _string_: Defaults to `null`.
	 *        - `'status'` _mixed_: Defaults to `null`.
	 *        - `'type'` _string_: Defaults to `null`.
	 *        - `'cookies'` _array_: Defaults to `[]`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'message' => null,
			'status' => null,
			'type' => null,
			'cookies' => []
		];
		parent::__construct($config + $defaults);

		if ($this->_config['message']) {
			$this->body = $this->_parseMessage($this->_config['message']);
		}
		if ($this->headers('Transfer-Encoding')) {
			$this->body = $this->_httpChunkedDecode($this->body);
		}
		if ($status = $this->_config['status']) {
			$this->status($status);
		}
		if ($cookies = $this->headers('Set-Cookie')) {
			$this->_parseCookies($cookies);
		}
		if ($cookies = $this->_config['cookies']) {
			$this->cookies($cookies);
		}
		if ($type = $this->_config['type']) {
			$this->type($type);
		}
		if (!$header = $this->headers('Content-Type')) {
			return;
		}
		$header = is_array($header) ? end($header) : $header;
		preg_match('/([-\w\/\.+]+)(;\s*?charset=(.+))?/i', $header, $match);

		if (isset($match[1])) {
			$this->type(trim($match[1]));
		}
		if (isset($match[3])) {
			$this->encoding = strtoupper(trim($match[3]));
		}
	}

	/**
	 * Add data to or compile and return the HTTP message body, optionally decoding its parts
	 * according to content type.
	 *
	 * @see lithium\net\Message::body()
	 * @see lithium\net\http\Message::_decode()
	 * @param mixed $data
	 * @param array $options
	 *        - `'buffer'` _integer_: split the body string
	 *        - `'encode'` _boolean_: encode the body based on the content type
	 *        - `'decode'` _boolean_: decode the body based on the content type
	 * @return array
	 */
	public function body($data = null, $options = []) {
		$defaults = ['decode' => true];
		return parent::body($data, $options + $defaults);
	}

	/**
	 * Set and get the status for the response.
	 *
	 * @param string $key Optional. Set to `'code'` or `'message'` to return just the code
	 *        or message of the status, otherwise returns the full status header.
	 * @param string|null $status The code or message of the status you wish to set.
	 * @return string|boolean Returns the full HTTP status, with version, code and message or
	 *         dending on $key just the code or message.
	 */
	public function status($key = null, $status = null) {
		if ($status === null) {
			$status = $key;
		}
		if ($status) {
			$this->status = ['code' => null, 'message' => null];

			if (is_array($status)) {
				$key = null;
				$this->status = $status + $this->status;
			} elseif (is_numeric($status) && isset($this->_statuses[$status])) {
				$this->status = ['code' => $status, 'message' => $this->_statuses[$status]];
			} else {
				$statuses = array_flip($this->_statuses);

				if (isset($statuses[$status])) {
					$this->status = ['code' => $statuses[$status], 'message' => $status];
				}
			}
		}
		if (!isset($this->_statuses[$this->status['code']])) {
			return false;
		}
		if (isset($this->status[$key])) {
			return $this->status[$key];
		}
		return "{$this->protocol} {$this->status['code']} {$this->status['message']}";
	}

	/**
	 * Add a cookie to header output, or return a single cookie or full cookie list.
	 *
	 * This function's parameters are designed to be analogous to setcookie(). Function parameters
	 * `expire`, `path`, `domain`, `secure`, and `httponly` may be passed in as an associative array
	 * alongside `value` inside `$value`.
	 *
	 * NOTE: Cookies values are expected to be scalar. This function will not serialize cookie values.
	 * If you wish to store a non-scalar value, you must serialize the data first.
	 *
	 * NOTE: Cookie values are stored as an associative array containing at minimum a `value` key.
	 * Cookies which have been set multiple times do not overwrite each other.  Rather they are stored
	 * as an array of associative arrays.
	 *
	 * @link http://php.net/function.setcookie.php
	 * @param string $key
	 * @param string $value
	 * @return mixed
	 */
	public function cookies($key = null, $value = null) {
		if (!$key) {
			$key = $this->cookies;
			$this->cookies = [];
		}
		if (is_array($key)) {
			foreach ($key as $cookie => $value) {
				$this->cookies($cookie, $value);
			}
		} elseif (is_string($key)) {
			if ($value === null) {
				return isset($this->cookies[$key]) ? $this->cookies[$key] : null;
			}
			if ($value === false) {
				unset($this->cookies[$key]);
			} else {
				if (is_array($value)) {
					if (array_values($value) === $value) {
						foreach ($value as $i => $set) {
							if (!is_array($set)) {
								$value[$i] = ['value' => $set];
							}
						}
					}
				} else {
					$value = ['value' => $value];
				}
				if (isset($this->cookies[$key])) {
					$orig = $this->cookies[$key];
					if (array_values($orig) !== $orig) {
						$orig = [$orig];
					}
					if (array_values($value) !== $value) {
						$value = [$value];
					}
					$this->cookies[$key] = array_merge($orig, $value);
				} else {
					$this->cookies[$key] = $value;
				}
			}
		}
		return $this->cookies;
	}

	/**
	 * Render `Set-Cookie` headers, urlencoding invalid characters.
	 *
	 * NOTE: Technically '+' is a valid character, but many browsers erroneously convert these to
	 * spaces, so we must escape this too.
	 *
	 * @return array Array of `Set-Cookie` headers or `null` if no cookies to set.
	 */
	protected function _cookies() {
		$cookies = [];
		foreach ($this->cookies() as $name => $value) {
			if (!isset($value['value'])) {
				foreach ($value as $set) {
					$cookies[] = compact('name') + $set;
				}
			} else {
				$cookies[] = compact('name') + $value;
			}
		}
		$invalid = str_split(",; \+\t\r\n\013\014");
		$replace = array_map('rawurlencode', $invalid);
		$replace = array_combine($invalid, $replace);

		foreach ($cookies as &$cookie) {
			if (!is_scalar($cookie['value'])) {
				$message = "Non-scalar value cannot be rendered for cookie `{$cookie['name']}`";
				throw new UnexpectedValueException($message);
			}
			$value = strtr($cookie['value'], $replace);
			$header = $cookie['name'] . '=' . $value;

			if (!empty($cookie['expires'])) {
				if (is_string($cookie['expires'])) {
					$cookie['expires'] = strtotime($cookie['expires']);
				}
				$header .= '; Expires=' . gmdate('D, d-M-Y H:i:s', $cookie['expires']) . ' GMT';
			}
			if (!empty($cookie['path'])) {
				$header .= '; Path=' . strtr($cookie['path'], $replace);
			}
			if (!empty($cookie['domain'])) {
				$header .= '; Domain=' . strtr($cookie['domain'], $replace);
			}
			if (!empty($cookie['secure'])) {
				$header .= '; Secure';
			}
			if (!empty($cookie['httponly'])) {
				$header .= '; HttpOnly';
			}
			$cookie = $header;
		}
		return $cookies ?: null;
	}

	/**
	 * Looks at the WWW-Authenticate. Will return array of key/values if digest.
	 *
	 * @param string $header value of WWW-Authenticate
	 * @return array
	 */
	public function digest() {
		if (empty($this->headers['WWW-Authenticate'])) {
			return [];
		}
		$auth = $this->_classes['auth'];
		return $auth::decode($this->headers['WWW-Authenticate']);
	}

	/**
	 * Accepts an entire HTTP message including headers and body, and parses it into a message body
	 * an array of headers, and the HTTP status.
	 *
	 * @param string $body The full body of the message.
	 * @return After parsing out other message components, returns just the message body.
	 */
	protected function _parseMessage($body) {
		if (!($parts = explode("\r\n\r\n", $body, 2)) || count($parts) === 1) {
			return trim($body);
		}
		list($headers, $body) = $parts;
		$headers = str_replace("\r", "", explode("\n", $headers));

		if (array_filter($headers) === []) {
			return trim($body);
		}
		preg_match('/HTTP\/(\d+\.\d+)\s+(\d+)(?:\s+(.*))?/i', array_shift($headers), $match);
		$this->headers($headers, false);

		if (!$match) {
			return trim($body);
		}
		list($line, $this->version, $code) = $match;
		if (isset($this->_statuses[$code])) {
			$message = $this->_statuses[$code];
		}
		if (isset($match[3])) {
			$message = $match[3];
		}
		$this->status = compact('code', 'message') + $this->status;
		$this->protocol = "HTTP/{$this->version}";
		return $body;
	}

	/**
	 * Parse `Set-Cookie` headers.
	 *
	 * @param array $headers Array of `Set-Cookie` headers or `null` if no cookies to set.
	 */
	protected function _parseCookies($headers) {
		foreach ((array) $headers as $header) {
			$parts = array_map('trim', array_filter(explode('; ', $header)));
			$cookie = array_shift($parts);
			list($name, $value) = array_map('urldecode', explode('=', $cookie, 2)) + ['',''];

			$options = [];
			foreach ($parts as $part) {
				$part = array_map('urldecode', explode('=', $part, 2)) + ['',''];
				$options[strtolower($part[0])] = $part[1] ?: true;
			}
			if (isset($options['expires'])) {
				$options['expires'] = strtotime($options['expires']);
			}
			$this->cookies($name, compact('value') + $options);
		}
	}

	/**
	 * Decodes content bodies transferred with HTTP chunked encoding.
	 *
	 * @link http://en.wikipedia.org/wiki/Chunked_transfer_encoding Wikipedia: Chunked encoding
	 * @param string $body A chunked HTTP message body.
	 * @return string Returns the value of `$body` with chunks decoded, but only if the value of the
	 *         `Transfer-Encoding` header is set to `'chunked'`. Otherwise, returns `$body`
	 *         unmodified.
	 */
	protected function _httpChunkedDecode($body) {
		if (stripos($this->headers('Transfer-Encoding'), 'chunked') === false) {
			return $body;
		}
		$stream = fopen('data://text/plain;base64,' . base64_encode($body), 'r');
		stream_filter_append($stream, 'dechunk');
		return trim(stream_get_contents($stream));
	}

	/**
	 * Return the response as a string.
	 *
	 * @return string
	 */
	public function __toString() {
		if ($type = $this->headers('Content-Type')) {
			$this->headers('Content-Type', "{$type};charset={$this->encoding}");
		}
		if ($cookies = $this->_cookies()) {
			$this->headers('Set-Cookie', $cookies);
		}
		$body = join("\r\n", (array) $this->body);
		$headers = join("\r\n", $this->headers());
		$response = [$this->status(), $headers, "", $body];
		return join("\r\n", $response);
	}
}

?>