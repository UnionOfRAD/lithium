<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\net;

/**
 * Abstract class for connecting to sockets with various adapters
 * Curl, Stream, Context adapters are available.
 *
 */
abstract class Socket extends \lithium\core\Object {

	/**
	 * the resource for the current connection
	 *
	 * @var resource
	 */
	protected $_resource = null;

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *              - `'persistent'`: Use a persistent connection (defaults to `false`).
	 *              - `'protocol'`: Transfer protocol to use (defaults to `'tcp'`).
	 *              - `'host'`: Host name or address (defaults to `'localhost'`).
	 *              - `'login'`: Username for a login (defaults to `'root'`).
	 *              - `'password'`: Password for a login (defaults to `''`).
	 *              - `'port'`: Host port (defaults to `80`).
	 *              - `'timeout'`: Seconds after opening the socket times out (defaults to `30`).
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'persistent' => false,
			'protocol'   => 'tcp',
			'host'       => 'localhost',
			'login'      => 'root',
			'password'   => '',
			'port'       => 80,
			'timeout'    => 30
		);
		parent::__construct((array) $config + $defaults);
	}

	/**
	 * Opens the socket and sets `Socket::$_resource`.
	 *
	 * @return booelan|resource The open resource on success, `false` otherwise.
	 */
	abstract public function open();

	/**
	 * Closes the socket and unsets `Socket::$_resource`.
	 *
	 * @return boolean `true` on success, `false` otherwise.
	 */
	abstract public function close();

	/**
	 * Test for the end-of-file on the socket.
	 *
	 * @return boolean `true` if end has been reached, `false` otherwise.
	 */
	abstract public function eof();

	/**
	 * Reads from the socket.
	 *
	 * @return mixed The read contents, or `false` if reading failed.
	 */
	abstract public function read();

	/**
	 * Writes data to the socket.
	 *
	 * @param mixed $data
	 * @return boolean `true` if data has been succesfully written, `false` otherwise.
	 */
	abstract public function write($data);

	/**
	 * Aggregates read and write methods into a coherent request response
	 *
	 * @param mixed $request array or object like `\lithium\net\http\Request`
	 * @params array $options
	 *                - path: path for the current request
	 *                - classes: array of classes to use
	 *                    - response: a class to use for the response
	 * @return boolean response string or object like `\lithium\net\http\Response`
	 */
	abstract public function send($message, array $options = array());

	/**
	 * Sets the timeout on the socket *connection*.
	 *
	 * @param integer $time Seconds after the connection times out.
	 * @return booelan `true` if timeout has been set, `false` otherwise.
	 */
	abstract public function timeout($time);

	/**
	 * Sets the encoding of the socket connection.
	 *
	 * @param string $charset The character set to use.
	 * @return boolean `true` if encoding has been set, `false` otherwise.
	 */
	abstract public function encoding($charset);

	/**
	 * Destructor.
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->close();
	}

	/**
	 * Returns the resource.
	 *
	 * @return resource|void
	 */
	public function resource() {
		return $this->_resource;
	}
}

?>