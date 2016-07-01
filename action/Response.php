<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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
	protected $_classes = [
		'router' => 'lithium\net\http\Router',
		'media' => 'lithium\net\http\Media',
		'auth' => 'lithium\net\http\Auth'
	];

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = ['classes' => 'merge'];

	/**
	 * Constructor. Adds config values to the public properties when a new object is created.
	 * Config options also include default values for `Response::body()` when called from
	 * `Response::render()`.
	 *
	 * @see lithium\net\http\Message::body()
	 * @see lithium\net\http\Response::__construct()
	 * @see lithium\net\http\Message::__construct()
	 * @see lithium\net\Message::__construct()
	 * @param array $config The available configuration options are the following. Further
	 *        options are inherited from the parent classes.
	 *        - `'buffer'` _integer_: Defaults to `null`
	 *        - `'decode'` _boolean_: Defaults to `null`.
	 *        - `'location'` _array|string|null_: Defaults to `null`.
	 *        - `'request'` _object_: Defaults to `null`.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'buffer' => 8192,
			'location' => null,
			'request' => null,
			'decode' => false
		];
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
		$router = $this->_classes['router'];

		if ($this->_config['location']) {
			$location = $router::match($this->_config['location'], $this->_config['request']);
			$this->headers('Location', $location);
		}
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
			$headers = [
				'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
				'Cache-Control' => [
					'no-store, no-cache, must-revalidate',
					'post-check=0, pre-check=0',
					'max-age=0'
				],
				'Pragma' => 'no-cache'
			];
		} else {
			$expires = is_int($expires) ? $expires : strtotime($expires);

			$headers = [
				'Expires' => gmdate('D, d M Y H:i:s', $expires) . ' GMT',
				'Cache-Control' => 'max-age=' . ($expires - time()),
				'Pragma' => 'cache'
			];
		}
		$this->headers($headers);
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
	 * Render a response by writing headers and output. Output is echoed in
	 * chunks because of an issue where `echo` time increases exponentially
	 * on long message bodies.
	 *
	 * Reponses which have a `Location` header set are indicating a
	 * redirect, will get their status code automatically adjusted to `302`
	 * (Found/Moved Temporarily) in case the status code before was `200`
	 * (OK). This is to allow easy redirects by setting just the `Location`
	 * header and is assumed to be the original intent of the user.
	 *
	 * On responses with status codes `204` (No Content) and `302` (Found)
	 * a message body - even if one is set - will never be send. These
	 * status codes either don't have a message body as per their nature or
	 * they are ignored and can thus be omitted for  performance reasons.
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
		if ($cookies = $this->_cookies()) {
			$this->headers('Set-Cookie', $cookies);
		}
		$this->_writeHeaders($this->status() ?: $this->status(500));
		$this->_writeHeaders($this->headers(), $code);

		if ($this->status['code'] === 302 || $this->status['code'] === 204) {
			return;
		}
		foreach ($this->body(null, $this->_config) as $chunk) {
			echo $chunk;
		}
	}

	/**
	 * Casts the Response object to a string.  This doesn't actually return a string, but does
	 * a direct render and returns an empty string.
	 *
	 * @return string Just an empty string to satify requirements of this magic method.
	 */
	public function __toString() {
		$this->render();
		return '';
	}

	/**
	 * Writes raw headers to output.
	 *
	 * @param string|array $headers Either a raw header string, or an array of header strings. Use
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

	/* Deprecated / BC */

	/**
	 * Expands on `\net\http\Message::headers()` with some magic conversions for shorthand headers.
	 *
	 * @deprecated This method will be removed in a future version. Note that the parent `header()`
	 *             wil continue to exist.
	 * @param string|array $key
	 * @param mixed $value
	 * @param boolean $replace
	 * @return mixed
	 */
	public function headers($key = null, $value = null, $replace = true) {
		if ($key === 'download' || $key === 'Download') {
			$message  = "Shorthand header `Download` with `<FILENAME>` has been deprecated ";
			$message .= "because it's too magic. Please use `Content-Disposition` ";
			$message .= "with `attachment; filename=\"<FILENAME>\"` instead.";
			trigger_error($message, E_USER_DEPRECATED);

			$key = 'Content-Disposition';
			$value = 'attachment; filename="' . $value . '"';
		}
		return parent::headers($key, $value, $replace);
	}
}

?>