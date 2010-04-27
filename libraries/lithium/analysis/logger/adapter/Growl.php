<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\analysis\logger\adapter;

use \Exception;
use \lithium\util\Inflector;

/**
 * The `Growl` logger implements support for the [ Growl](http://growl.info/) notification system
 * for Mac OS X. Writing to this logger will display small, customizable status messages on the
 * screen.
 */
class Growl extends \lithium\core\Object {

	/**
	 * The Growl protocol version used to send messages.
	 */
	const PROTOCOL_VERSION = 1;

	/**
	 * There are two types of messages sent to Growl: one to register applications, and one to send
	 * notifications. This type registers the application with Growl's settings.
	 */
	const TYPE_REG = 0;

	/**
	 * This message type is for sending notifications to Growl.
	 */
	const TYPE_NOTIFY = 1;

	/**
	 * Holds the socket connection resource used to send messages to Growl.
	 *
	 * @var resource
	 */
	public $connection = null;

	/**
	 * Flag indicating whether the logger has successfully registered with the Growl server.
	 * Registration only needs to happen once, but may fail for several reasons, including inability
	 * to connect to the server, or the server requires a password which has not been specified.
	 *
	 * @var boolean
	 */
	protected $_registered = false;

	/**
	 * Growl logger constructor. Accepts an array of settings which are merged with the default
	 * settings and used to create the connection and handle notifications.
	 *
	 * @see lithium\analysis\Logger::write()
	 * @param array $config The settings to configure the logger. Available settings are as follows:
	 *              - `'name`' _string_: The name of the application as it should appear in Growl's
	 *                system settings. Defaults to the directory name containing your application.
	 *              - `'host'` _string_: The Growl host with which to communicate, usually your
	 *                local machine. Use this setting to send notifications to another machine on
	 *                the network. Defaults to `'127.0.0.1'`.
	 *              - `'port'` _integer_: Port of the host machine. Defaults to the standard Growl
	 *                port, `9887`.
	 *              - `'password'` _string_: Only required if the host machine requires a password.
	 *                If notification or registration fails, check this against the host machine's
	 *                Growl settings.
	 *              - '`protocol'` _string_: Protocol to use when opening socket communication to
	 *                Growl. Defaults to `'udp'`.
	 *              - `'title'` _string_: The default title to display when showing Growl messages.
	 *                The default value is the same as `'name'`, but can be changed on a per-message
	 *                basis by specifying a `'title'` key in the `$options` parameter of
	 *                `Logger::write()`.
	 *              - `'notification'` _array_: A list of message types you wish to register with
	 *                Growl to be able to send. Defaults to `array('Errors', 'Messages')`.
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$name = basename(LITHIUM_APP_PATH);
		$defaults = array(
			'name'     => $name,
			'host'     => '127.0.0.1',
			'port'     => 9887,
			'password' => '',
			'protocol' => 'udp',
			'title'    => Inflector::humanize($name),
			'notifications' => array('Errors', 'Messages')
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Writes `$message` to a new Growl notification.
	 *
	 * @param string $type Not used (all notifications are of the same type).
	 * @param string $message Message to be shown.
	 * @return boolean `True` on successful write, `false` otherwise.
	 */
	public function write($type, $message) {
		if (!$this->_register()) {

		}
		$_self =& $this;

		return function($self, $params, $chain) use (&$_self) {
			return $_self->notify($params['message']);
		};
	}

	/**
	 * Posts a new notification to the Growl server.
	 *
	 * @param string $description Message to be displayed.
	 * @param array $options Options consists of:
	 *        -'title': The title of the displayed notification. Displays the
	 *         name of the application's parent folder by default.
	 * @return boolean `True` on successful write, `false` otherwise.
	 */
	public function notify($description = '', $options = array()) {
		if (!$this->_register()) {
			return false;
		}
		$defaults = array('sticky' => false, 'priority' => 0, 'type' => 'Messages');
		$options += $defaults + array('title' => $this->_config['title']);
		$type = $options['type'];
		$title = $options['title'];

		$message = compact('type', 'title', 'description') + array('app' => $this->_config['name']);
		$message = array_map('utf8_encode', $message);

        $flags = ($options['priority'] & 7) * 2;
		$flags = ($options['priority'] < 0) ? $flags |= 8 : $flags;
		$flags = ($options['sticky']) ? $flags | 1 : $flags;

		$params = array('c2n5', static::PROTOCOL_VERSION, static::TYPE_NOTIFY, $flags);
		$lengths = array_map('strlen', $message);

		$data = call_user_func_array('pack', array_merge($params, $lengths));
		$data .= join('', $message);
		$data .= pack('H32', md5($data . $this->_config['password']));

		if (fwrite($this->connection, $data, strlen($data)) === false) {
			throw new Exception('Could not send notification to Growl Server.');
		}
		return true;
	}

	/**
	 * Growl server connection registration and initialization.
	 *
	 * @return boolean `True` on successful write, `false` otherwise.
	 */
	protected function _register() {
		if ($this->_registered) {
			return true;
		}

		if (!$this->connection) {
			$conn = $this->_config['protocol'] . '://' . $this->_config['host'];

			if (!$this->connection = fsockopen($conn, $this->_config['port'], $message, $code)) {
				throw new Exception("Growl connection failed: ({$code}) {$message}");
			}
		}
		$app      = utf8_encode($this->_config['name']);
		$nameEnc  = $defaultEnc = '';

		foreach ($this->_config['notifications'] as $i => $name) {
			$name = utf8_encode($name);
			$nameEnc .= pack('n', strlen($name)) . $name;
			$defaultEnc .= pack('c', $i);
		}
		$data = pack('c2nc2', static::PROTOCOL_VERSION, static::TYPE_REG, strlen($app), $i, $i);
		$data .= $app . $nameEnc . $defaultEnc;
		$data .= pack('H32', md5($data . $this->_config['password']));

		if (fwrite($this->connection, $data, strlen($data)) === false) {
			throw new Exception('Could not send registration to Growl Server.');
		}
		return $this->_registered = true;
	}

	/**
	 * Destructor method. Closes and releases the socket connection to Growl.
	 *
	 * @return void
	 */
	public function __destruct() {
		if (is_resource($this->connection)) {
			fclose($this->connection);
			unset($this->connection);
		}
	}
}

?>