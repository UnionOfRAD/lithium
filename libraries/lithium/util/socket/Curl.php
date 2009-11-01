<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace lithium\util\socket;

class Curl extends \lithium\util\Socket {

	public $options = array();

	public function open() {
		$config = $this->_config;

		if (empty($config['protocol']) || empty($config['host'])) {
			return false;
		}

		$url = "{$config['protocol']}://{$config['host']}";
		$this->_resource = curl_init($url);
		curl_setopt($this->_resource, CURLOPT_PORT, $config['port']);
		curl_setopt($this->_resource, CURLOPT_HEADER, 0);
		curl_setopt($this->_resource, CURLOPT_RETURNTRANSFER, true);

		if (is_resource($this->_resource)) {
			$this->_isConnected = true;

			$this->timeout($config['timeout']);

			if (!empty($config['encoding'])) {
				$this->encoding($config['encoding']);
			}
		}

		return $this->_resource;
	}

	public function close() {
		if (!is_resource($this->_resource)) {
			return true;
		}
		curl_close($this->_resource);
		if (is_resource($this->_resource)) {
			$this->close();
		}
		return true;
	}

	public function eof() {

	}

	public function read() {
		if (!is_resource($this->_resource)) {
			return false;
		}
		curl_setopt_array($this->_resource, $this->options);
		return curl_exec($this->_resource);
	}

	public function write($data) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		curl_setopt_array($this->_resource, $this->options);
		return curl_exec($this->_resource);
	}

	public function timeout($time) {
		if (!is_resource($this->_resource)) {
			return false;
		}
		curl_setopt($this->_resource, CURLOPT_CONNECTTIMEOUT, $time);
	}

	public function encoding($charset) {
	}

	public function set($flags, $value = null) {
		if ($value !== null) {
			$flags = array($flags => $value);
		}
		$this->options += $flags;
	}
}

?>