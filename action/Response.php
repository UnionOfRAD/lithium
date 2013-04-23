<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\action;

/**
 * A `Response` object is typically instantiated automatically by the `Controller`. It is assigned
 * any headers set in the course of the request, as well as any content rendered by the
 * `Controller`. Once completed, the `Controller` returns the `Response` object to the `Dispatcher`.
 *
 * The `Response` object is responsible for writing its body content to output, and writing any
 * headers to the browser.
 *
 * @see lithium\action\Dispatcher
 * @see lithium\action\Controller
 */
class Response extends \lithium\net\http\Response {

	/**
	 * Classes used by Response.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'router' => 'lithium\net\http\Router',
		'media' => 'lithium\net\http\Media',
		'auth' => 'lithium\net\http\Auth'
	);

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('classes' => 'merge');

	/**
	 * Adds config values to the public properties when a new object is created. Config options
	 * also include default values for `Response::body()` when called from `Response::render()`.
	 *
	 * @see lithium\net\http\Message::body()
	 * @param array $config Configuration options : default value
	 *        - `'protocol'` _string_: null
	 *        - `'version'` _string_: '1.1'
	 *        - `'headers'` _array_: array()
	 *        - `'body'` _mixed_: null
	 *        - `'message'` _string_: null
	 *        - `'status'` _mixed_: null
	 *        - `'type'` _string_: null
	 *        - `'buffer'` _integer_: null
	 *        - `'decode'` _boolean_: null
	 *        - `'location'` _mixed_: null
	 *        - `'request'` _object_: null
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'buffer' => 8192,
			'location' => null,
			'request' => null,
			'decode' => false
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Sets the Location header using `$config['location']` and `$config['request']` passed in
	 * through the constructor if provided.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		if ($this->_config['location']) {
			$router = $this->_classes['router'];
			$location = $router::match($this->_config['location'], $this->_config['request']);
			$this->headers('Location', $location);
		}
	}

	/**
	 * Expands on `\net\http\Message::headers()` with some magic conversions for shorthand headers.
	 *
	 * @param string $key
	 * @param string $value
	 * @param boolean $replace
	 * @return mixed
	 */
	public function headers($key = null, $value = null, $replace = true) {
		if (is_string($key) && strtolower($key) == 'download') {
			$key = 'Content-Disposition';
			$value = 'attachment; filename="' . $value . '"';
		}
		return parent::headers($key, $value, $replace);
	}

	/**
	 * Controls how or whether the client browser and web proxies should cache this response.
	 *
	 * @param mixed $expires This can be a Unix timestamp indicating when the page expires, or a
	 *        string indicating the relative time offset that a page should expire, i.e. `"+5 hours".
	 *        Finally, `$expires` can be set to `false` to completely disable browser or proxy
	 *        caching.
	 * @return void
	 */
	public function cache($expires) {
		if ($expires === false) {
			return $this->headers(array(
				'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
				'Cache-Control' => array(
					'no-store, no-cache, must-revalidate',
					'post-check=0, pre-check=0',
					'max-age=0'
				),
				'Pragma' => 'no-cache'
			));
		}
		$expires = is_int($expires) ? $expires : strtotime($expires);

		return $this->headers(array(
			'Expires' => gmdate('D, d M Y H:i:s', $expires) . ' GMT',
			'Cache-Control' => 'max-age=' . ($expires - time()),
			'Pragma' => 'cache'
		));
	}

	/**
	 * Sets/Gets the content type. If `'type'` is null, the method will attempt to determine the
	 * type from the params, then from the environment setting
	 *
	 * @param string $type a full content type i.e. `'application/json'` or simple name `'json'`
	 * @return string A simple content type name, i.e. `'html'`, `'xml'`, `'json'`, etc., depending
	 *         on the content type of the request.
	 */
	public function type($type = null) {
		if ($type === null && $this->_type === null) {
			$type = 'html';
		}
		return parent::type($type);
	}

	/**
	 * Render a response by writing headers and output. Output is echoed in chunks because of an
	 * issue where `echo` time increases exponentially on long message bodies.
	 *
	 * @return void
	 */
	public function render() {
		$code = null;
		if (isset($this->headers['location']) || isset($this->headers['Location'])) {
			if ($this->status['code'] === 200) {
				$this->status(302);
			}
			$code = $this->status['code'];
		}
		$this->_writeHeaders($this->status() ?: $this->status(500));
		$this->_writeHeaders($this->headers(), $code);

		if ($this->status['code'] === 302 || $this->status['code'] === 204) {
			return;
		}
		$chunked = $this->body(null, $this->_config);

		foreach ($chunked as $chunk) {
			echo $chunk;
		}
	}

	/**
	 * Casts the Response object to a string.  This doesn't actually return a string, but does
	 * a direct render and returns null.
	 *
	 * @return string An empty string.
	 */
	public function __toString() {
		$this->render();
		return '';
	}

	/**
	 * Writes raw headers to output.
	 *
	 * @param string|array $header Either a raw header string, or an array of header strings. Use
	 *        an array if a single header must be written multiple times with different values.
	 *        Otherwise, additional values for duplicate headers will overwrite previous values.
	 * @param integer $code Optional. If present, forces a specific HTTP response code.  Used
	 *        primarily in conjunction with the 'Location' header.
	 * @return void
	 */
	protected function _writeHeaders($headers, $code = null) {
		foreach ((array) $headers as $header) {
			$code ? header($header, false, $code) : header($header, false);
		}
	}
}

?>