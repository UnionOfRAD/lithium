<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\action;

use \Exception;

/**
 * The `Response` instance is what the `Controller` returns to the `Dispatcher` as the product
 * of the view layer. See related classes for more details.
 *
 * @see lithium\action\Dispatcher
 * @see lithium\action\Controller
 */
class Response extends \lithium\http\Response {

	protected $_config = array();

	public function __construct($config = array()) {
		$defaults = array(
			'buffer' => 8192, 'request' => null, 'defaultType' => 'html'
		);
		if (!empty($config['request']) && is_object($config['request'])) {
			$this->type = $config['request']->type();
		}
		$this->_config = (array)$config + $defaults;
		parent::__construct($this->_config);
	}

	/**
	 * Content Type
	 *
	 * @return string
	 */
	public function type($type = null) {
		if (!empty($type)) {
			return $this->type = $type;
		}
		return $this->type ?: $this->_config['defaultType'];
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
				"no-store, no-cache, must-revalidate",
				"post-check=0, pre-check=0"
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
		if (isset($this->headers['location'])) {
			$code = 302;
		}
		$status = $this->status($code);
		if (!$status) {
			throw new Exception('Invalid status code');
		}

		$this->_writeHeader($status);

		foreach ($this->headers as $name => $value) {
			$key = strtolower($name);
			if ($key == 'location') {
				$this->_writeHeader("Location: {$value}", $this->status['code']);
			} elseif ($key == 'download') {
				$tmp = 'Content-Disposition: attachment;'
				 	. ' filename="' . $value . '"';
				$this->_writeHeader($tmp);
			} elseif (is_array($value)) {
				$this->_writeHeader(
					array_map(function($v) use ($name) { return "{$name}: {$v}"; }, $value)
				);
			} elseif (!is_numeric($name)) {
				$this->_writeHeader("{$name}: {$value}");
			}
		}
		$chunked = str_split(join("\r\n", (array)$this->body), $this->_config['buffer']);

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
	 * @param mixed $header Either a raw header string, or an array of header strings. Use an array
	 *        if a single header must be written multiple times with different values. Otherwise,
	 *        subsequent values with non-unique header names will overwrite previous values.
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