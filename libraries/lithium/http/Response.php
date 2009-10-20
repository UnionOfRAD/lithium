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
		if (!empty($config['message'])) {
			$parts = explode("\r\n\r\n", $config['message']);

			if (empty($parts)) {
				return false;
			}

			$headers = str_replace("\r", "", explode("\n", array_shift($parts)));

			if (array_filter($headers) == array()) {
				return;
			}
			preg_match('/HTTP\/(\d+\.\d+)\s+(\d+)\s+(.*)/i',
				array_shift($headers), $match
			);

			if (!empty($match)) {
				list($line, $this->version, $code, $message) = $match;
				$this->status = compact('code', 'message') + $this->status;
			}
			$this->protocol = "HTTP/{$this->version}";
			$this->headers($headers);

			if (!empty($this->headers['Content-Type'])) {
				preg_match('/^(.*?);charset=(.+)/i', $this->headers['Content-Type'], $match);

				if (!empty($match)) {
					$this->type = trim($match[1]);
					$this->charset = trim($match[2]);
				}
			}
			$body = implode("\r\n\r\n", $parts);
			if (isset($this->headers['Transfer-Encoding'])) {
				$body = $this->_chunkDecode($body);
			}
			$this->body($body);
			unset($config['message']);
		}

		foreach ((array)$config as $key => $value) {
			if (isset($this->{$key})) {
				$this->{$key} = $value;
			}
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

	/**
	* Decodes chunked body
	*
	* @return string
	*/
	protected function _chunkDecode($in) {
		$out = '';
		while($in != '') {
			$lf_pos = strpos($in, "\012");
			if($lf_pos === false) {
				$out .= $in;
				break;
			}
			$chunk_hex = trim(substr($in, 0, $lf_pos));
			$sc_pos = strpos($chunk_hex, ';');
			if($sc_pos !== false) {
				$chunk_hex = substr($chunk_hex, 0, $sc_pos);
			}
			if($chunk_hex == '') {
				$out .= substr($in, 0, $lf_pos);
				$in = substr($in, $lf_pos + 1);
				continue;
			}
			$chunk_len = hexdec($chunk_hex);
			if($chunk_len) {
				$out .= substr($in, $lf_pos + 1, $chunk_len);
				$in = substr($in, $lf_pos + 2 + $chunk_len);
			} else {
				$in = '';
			}
		}
		return $out;
	}
}

?>