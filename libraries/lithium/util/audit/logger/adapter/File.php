<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util\audit\logger\adapter;

use \SplFileInfo;
use \DirectoryIterator;

class File extends \lithium\core\Object {

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('path' => LITHIUM_APP_PATH . '/tmp/logs');
		parent::__construct($config + $defaults);
	}

	/**
	 * Appends $data to file $type.
	 *
	 * @param string $type
	 * @param string $message
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($type, $message) {
		$path = $this->_config['path'];

		return function($self, $params, $chain) use (&$path) {
			extract($params);
			return file_put_contents("$path/$type.log", "{$message}\n", FILE_APPEND);
		};
	}
}

?>