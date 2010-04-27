<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\action;

use \Exception;

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
	protected $_classes = array('router' => '\lithium\net\http\Router');

	public function __construct(array $config = array()) {
		$defaults = array(
			'buffer' => 8192, 'request' => null, 'location' => null, 'status' => null
		);
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		$this->status($this->_config['status']);
		unset($this->_config['status']);

		if ($this->_config['request'] && is_object($this->_config['request'])) {
			$this->type = $this->_config['request']->type();
		}
		if ($this->_config['location']) {
			$router = $this->_classes['router'];
			$location = $router::match($this->_config['location'], $this->_config['request']);
			$this->headers('location', $location);
		}
		parent::_init();
	}

	/**
	 * Content Type.
	 *
	 * @param string $type
	 * @return string
	 */
	public function type($type = null) {
		if ($type !== null) {
			return $this->type = $type;
		}
		return $this->type;
	}

	/**
	 * Disables HTTP caching for web clients and proxies.
	 *
	 * @return void
	 */
	public function disableCache() {
		$this->headers(array(
			'Expires' => "Mon, 26 Jul 1997 05:00:00 GMT",
			'Last-Modified' => gmdate("D, d M Y H:i:s") . " GMT",
			'Cache-Control' => array(
				"no-store, no-cache, must-revalidate", "post-check=0, pre-check=0"
			),
			'Pragma' => 'no-cache'
		));
	}

	/**
	 * Render a response by writing headers and output. Output is echoed in chunks because of an
	 * issue where `echo` time increases exponentially on long message bodies.
	 *
	 * @return void
	 */
	public function render() {
		$code = null;

		if (isset($this->headers['location']) && $this->status['code'] === 200) {
			$code = 302;
		}

		if (!$status = $this->status($code)) {
			throw new Exception('Invalid status code');
		}
		$this->_writeHeader($status);

		foreach ($this->headers as $name => $value) {
			$key = strtolower($name);

			if ($key == 'location') {
				$this->_writeHeader("Location: {$value}", $this->status['code']);
			} elseif ($key == 'download') {
				$this->_writeHeader('Content-Disposition: attachment; filename="' . $value . '"');
			} elseif (is_array($value)) {
				$this->_writeHeader(
					array_map(function($v) use ($name) { return "{$name}: {$v}"; }, $value)
				);
			} elseif (!is_numeric($name)) {
				$this->_writeHeader("{$name}: {$value}");
			}
		}
		$chunked = str_split(join("\r\n", (array) $this->body), $this->_config['buffer']);

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
	protected function _writeHeader($header, $code = null) {
		if (is_array($header)) {
			array_map(function($h) { header($h, false); }, $header);
			return;
		}
		$code ? header($header, true) : header($header, true, $code);
	}
}

?>