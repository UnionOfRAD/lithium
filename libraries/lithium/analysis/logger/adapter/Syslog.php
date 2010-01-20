<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\analysis\logger\adapter;

/**
 * The Syslog adapter facilitates logging messages to a syslogd backend.
 */
class Syslog extends \lithium\core\Object {

	/**
	 * The last connection to have opened syslog. This will determine whether or
	 * not the log needs to be closed and reopened.
	 *
	 * @var string
	 */
	protected static $_lastOpenedBy;

	/**
	 * Class constructor
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'identity' => false,
			'options'  => LOG_ODELAY,
			'facility' => LOG_USER,
			'priority' => LOG_INFO
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Appends `$data` to file `$type`.
	 *
	 * @param string $type
	 * @param string $message
	 * @return boolean `True` on successful write, `false` otherwise.
	 */
	public function write($type, $message) {
		if (static::$_lastOpenedBy != $type) {
			closelog(); // Close previously opened log (doesn't matter if none opened)
			openlog($this->_config['identity'], $this->_config['options'], $this->_config['facility']);
			static::$_lastOpenedBy = $type;
		}

		$priority = $this->_config['priority'];

		return function($self, $params, $chain) use ($priority) {
			return syslog($priority, $params['message']);
		};
	}
}

?>