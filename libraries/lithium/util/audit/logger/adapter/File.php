<?php

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
			$message .= "\n";
			return file_put_contents("$path/$type.log", $message, FILE_APPEND);
		};

	}
}

?>