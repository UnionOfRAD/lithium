<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util\socket;

use \Exception;

/**
 * Socket Stream class
 *
 */
class Stream extends \lithium\util\Socket {

	/**
	 * open the connection
	 *
	 * @return resource
	 */
	public function open() {
		$config = $this->_config;

		if (empty($config['protocol']) || empty($config['host'])) {
			return false;
		}

		$host = "{$config['protocol']}://{$config['host']}";

		if ($config['persistent']) {
			$this->_resource = pfsockopen($host, $config['port'], $errorCode, $errorMessage);
		} else {
			$this->_resource = fsockopen($host, $config['port'], $errorCode, $errorMessage);
		}

		if (!empty($errorCode) || !empty($errorMessage)) {
			throw new Exception($errorMessage, $errorCode);
		}

		$this->timeout($config['timeout']);

		if (!empty($config['encoding'])) {
			$this->encoding($config['encoding']);
		}

		return $this->_resource;
	}

	/**
	 * Close the connection
	 *
	 * @return boolean
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
	 * end of file pointer
	 *
	 * @return boolean
	 */
	public function eof() {
		if (!is_resource($this->_resource)) {
			return true;
		}
		return feof($this->_resource);
	}

	/**
	 * read from stream resource
	 *
	 * @param integer $length
	 * @param integer $offset
	 * @return string
	 */
	public function read($length = null, $offset = null) {
		if (!is_resource($this->_resource)) {
			return false;
		}

		$buffer = null;
		if (is_null($length)) {
			$buffer = stream_get_contents($this->_resource);
		} else {
			$buffer = stream_get_contents($this->_resource, $length, $offset);
		}

		return $buffer;
	}

	/**
	 * write to stream
	 *
	 * @param string $data
	 * @return boolean
	 */
	public function write($data) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		return fwrite($this->_resource, $data, strlen($data));
	}

	/**
	 * set the timeout
	 *
	 * @param integer $time
	 * @return void
	 */
	public function timeout($time) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		stream_set_timeout($this->_resource, $time);
	}

	/**
	 * set the encoding
	 *
	 * @param string $charset
	 * @return void
	 */
	public function encoding($charset) {
		if (function_exists('stream_encoding')) {
			if (!is_resource($this->_resource)) {
				return false;
			}
			stream_encoding($this->_resource, $charset);
		}
	}
}

?>