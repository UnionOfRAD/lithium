<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net\socket;

/**
 * A socket adapter that uses PHP stream contexts.
 */
class Context extends \lithium\net\Socket {

	/**
	 * Connection timeout value.
	 *
	 * @var integer
	 */
	protected $_timeout = 30;

	/**
	 * Content of the stream
	 *
	 * @var string
	 */
	protected $_content = null;

	/**
	 * Opens the socket and sets its timeout value.
	 *
	 * @return boolean Success.
	 */
	public function open() {
		$config = $this->_config;
		$this->timeout($config['timeout']);
	}

	/**
	 * Closes the socket connection.
	 *
	 * @return boolean Success.
	 */
	public function close() {
		if (!is_resource($this->_resource)) {
			return true;
		}
		fclose($this->_resource);
		if (is_resource($this->_resource)) {
			$this->close();
		}
		return true;
	}

	/**
	 * End of file test for this socket connection. Does not apply to this implementation.
	 *
	 * @return boolean Success.
	 */
	public function eof() {
		return true;
	}

	/**
	 * Reads from the socket. Does not apply to this implementation.
	 *
	 * @return void
	 */
	public function read() {
		$content = $this->_content;

		$url = is_object($url) ? $content->to('url') : $url;
		$context = is_object($url)
			? $url->to('context', $options['context'])
			: array($options['wrapper'] => $options['context']);
		$this->connection = fopen($url, $options['mode'], false, stream_context_create($context));

		if ($this->connection) {
			$meta = stream_get_meta_data($this->connection);
			$headers = isset($meta['wrapper_data']) ? $meta['wrapper_data'] : array();
			$message = isset($headers[0]) ? $headers[0] : null;
			$body = stream_get_contents($this->connection);
		}
	}

	/**
	 * Writes to the socket. Does not apply to this implementation.
	 *
	 * @param string $data Data to write.
	 * @return boolean Success
	 */
	public function write($data) {
		return $this->_content = $data;
	}

	/**
	 * Sets the timeout on the socket *connection*.
	 *
	 * @param integer $time Seconds after the connection times out.
	 * @return booelan `true` if timeout has been set, `false` otherwise.
	 */
	public function timeout($time = null) {
		if ($time !== null) {
			$this->_timeout = $time;
		}
		return $this->_timeout;
	}

	/**
	 * Sets the encoding of the socket connection. Does not apply to this implementation.
	 *
	 * @param string $charset The character set to use.
	 * @return boolean `true` if encoding has been set, `false` otherwise.
	 */
	public function encoding($charset = null) {
		return false;
	}

	/**
	 * Send request and return response data
	 *
	 * @param object|string $url
	 *        - object with to('url) and to('context') methods
	 *        - string url like "php://temp"
	 * @param array $options
	 * @return string
	 */
	public function send($url, array $options = array()) {
		$defaults = array(
			'wrapper' => 'html', 'mode' => 'r',
		);
		$options += $defaults;

		$url = is_object($url) ? $url->to('url') : $url;
		$context = is_object($url)
			? $url->to('context', $options['context'])
			: array($options['wrapper'] => $options['context']);
		$this->connection = fopen($url, $options['mode'], false, stream_context_create($context));

		if ($this->connection) {
			$meta = stream_get_meta_data($this->connection);
			$headers = isset($meta['wrapper_data']) ? $meta['wrapper_data'] : array();
			$message = isset($headers[0]) ? $headers[0] : null;
			$body = stream_get_contents($this->connection);
			return compact('headers', 'message', 'body');
		}
	}
}

?>