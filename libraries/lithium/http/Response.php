<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\http;

class Response extends \lithium\http\Base {

	/**
	 * Status Code and Message
	 *
	 * @var array
	 **/
	public $status = array('code' => 200, 'message' => 'OK');

	/**
	 * headers
	 *
	 * @var array
	 **/
	public $headers = array();

	/**
	 * Content Type
	 *
	 * @var string
	 **/
	public $type = 'text/html';

	/**
	 * Character Set
	 *
	 * @var string
	 **/
	public $charset = 'UTF-8';

	/**
	 * the body
	 *
	 * @var array
	 **/
	public $body = array();

	/**
	* Status codes
	*
	* @var array
	*/
	protected $_statuses = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
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
		416 => 'Requested range not satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out'
	);

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		foreach ($config as $key => $value) {
			if (isset($this->{$key})) {
				$this->{$key} = $value;
			}
		}
		if (!empty($config['message'])) {
			$parts = explode("\r\n\r\n", $config['message'], 2);

			if (empty($parts)) {
				return false;
			}
			$headers = str_replace("\r", "", explode("\n", array_shift($parts)));

			if (empty($headers)) {
				return false;
			}
			preg_match('/HTTP\/(\d+\.\d+)\s+(\d+)\s+(\w+)/i',
				array_shift($headers), $match
			);
			if (!empty($match)) {
				list($line, $this->version,
					$this->status['code'], $this->status['message']
				) = $match;
			}
			$this->protocol = "HTTP/{$this->version}";

			$this->headers($headers);

			if (!empty($this->headers['Content-Type'])) {
				preg_match('/^(.*?);charset=(.+)/i',
					$this->headers['Content-Type'], $match
				);
				if (!empty($match)) {
					$this->type = trim($match[1]);
					$this->charset = trim($match[2]);
				}
			}

			$this->body(array_shift($parts));
		}
	}

	/**
	 * undocumented function
	 *
	 * @return string
	 *
	 **/
	public function status($key = null, $data = null) {
		if ($data === null) {
			$data = $key;
		}
		if (!empty($data)) {
			$this->status = array('code'=> null, 'message' => null);
			if (is_numeric($data) && isset($this->_statuses[$data])) {
				$this->status = array(
					'code' => $data, 'message' => $this->_statuses[$data]
				);
			} else {
				$statuses = array_flip($this->_statuses);
				if (isset($statuses[$data])) {
					$this->status = array(
						'code' => $statuses[$data], 'message' => $data
					);
				}
			}
		}
		if (!isset($this->_statuses[$this->status['code']])) {
			return false;
		}
		if (isset($this->status[$key])) {
			return $this->status[$key];
		}
		return "{$this->protocol}"
			. " {$this->status['code']} {$this->status['message']}";
	}

	/**
	* Return the response as a string
	*
	* @return string
	*/
	public function __toString() {
		$first = "{$this->protocol}"
			. " {$this->status['code']} {$this->status['message']}";

		$response = array(
			$first, join("\r\n", $this->headers()),
			"", $this->body()
		);

		$message = join("\r\n", $response);
		return $message;
	}
}

?>