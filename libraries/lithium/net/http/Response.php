<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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
	public $status = array('code' => 200, 'message' => 'OK');

	/**
	 * Headers.
	 *
	 * @var array
	 */
	public $headers = array();

	/**
	 * Content Type.
	 *
	 * @var string
	 */
	public $type = 'text/html';

	/**
	 * Character encoding.
	 *
	 * @var string
	 */
	public $encoding = 'UTF-8';

	/**
	 * The body.
	 *
	 * @var array
	 */
	public $body = array();

	/**
	 * Status codes.
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

	protected function _init() {
		parent::_init();
		$body = $this->_config['body'];

		if ($this->_config['body'] && !$this->_config['message']) {
			$this->body = $this->_config['body'];
		} elseif (($body = $this->_config['message']) && !$this->_config['body']) {
			$body = $this->_parseMessage($body);
		}

		if (isset($this->headers['Content-Type'])) {
			preg_match('/^(.*?);charset=(.+)/i', $this->headers['Content-Type'], $match);

			if ($match) {
				$this->type = trim($match[1]);
				$this->encoding = strtoupper(trim($match[2]));
			}
		}

		if (isset($this->headers['Transfer-Encoding'])) {
			$body = $this->_decode($body);
		}
		$this->body = $this->body ?: $body;
	}

	protected function _parseMessage($body) {
		if (!($parts = explode("\r\n\r\n", $body, 2)) || count($parts) == 1) {
			return $body;
		}
		list($headers, $body) = $parts;
		$headers = str_replace("\r", "", explode("\n", $headers));

		if (array_filter($headers) == array()) {
			return $body;
		}
		preg_match('/HTTP\/(\d+\.\d+)\s+(\d+)\s+(.*)/i', array_shift($headers), $match);
		$this->headers($headers);

		if (!$match) {
			return $body;
		}
		list($line, $this->version, $code, $message) = $match;
		$this->status = compact('code', 'message') + $this->status;
		$this->protocol = "HTTP/{$this->version}";
		return $body;
	}

	/**
	 * Set and get the status for the response
	 *
	 * @param string $key
	 * @param string $data
	 * @return string
	 */
	public function status($key = null, $data = null) {
		if ($data === null) {
			$data = $key;
		}
		if (!empty($data)) {
			$this->status = array('code' => null, 'message' => null);

			if (is_numeric($data) && isset($this->_statuses[$data])) {
				$this->status = array('code' => $data, 'message' => $this->_statuses[$data]);
			} else {
				$statuses = array_flip($this->_statuses);

				if (isset($statuses[$data])) {
					$this->status = array('code' => $statuses[$data], 'message' => $data);
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
	* Return the response as a string.
	*
	* @return string
	*/
	public function __toString() {
		if ($this->type != 'text/html' && !isset($this->headers['Content-Type'])) {
			$this->headers['Content-Type'] = $this->type;
		}
		$first = "{$this->protocol} {$this->status['code']} {$this->status['message']}";
		$response = array($first, join("\r\n", $this->headers()), "", $this->body());
		return join("\r\n", $response);
	}

	/**
	* Decodes based on transfer encoding body.
	*
	* @todo replace with stream wrapper dechunk
	* @param string $body
	* @return string
	*/
	protected function _decode($body) {
		if (stripos($this->headers['Transfer-Encoding'], 'chunked') !== false) {
			$decoded = null;
			while($body != '') {
				$left = strpos($body, "\012");
				if($left === false) {
					$decoded .= $body;
					break;
				}
				$chunk = substr($body, 0, $left);
				$next = strpos($chunk, ';');
				if($next !== false) {
					$chunk = substr($chunk, 0, $next);
				}
				if($chunk == '') {
					$decoded .= substr($body, 0, $left);
					$body = substr($body, $left + 1);
					continue;
				}
				$length = hexdec($chunk);
				if($length) {
					$decoded .= substr($body, $left + 1, $length);
					$body = substr($body, $left + 2 + $length);
				} else {
					$body = '';
				}
			}
			return $decoded;
		}

		return $body;
	}
}

?>