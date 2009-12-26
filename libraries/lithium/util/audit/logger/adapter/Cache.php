<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util\audit\logger\adapter;

use \lithium\util\String;
use \lithium\storage\Cache as Writer;

class Cache extends \lithium\core\Object {

	/**
	 * Class constructor
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'config' => null,
			'expiry' => '+999 days',
			'key' => 'log_{:type}_{:timestamp}'
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Appends `$data` to file `$type`.
	 *
	 * @param string $type
	 * @param string $message
	 * @return boolean True on successful write, false otherwise
	 */
	public function write($type, $message) {
		$config = $this->_config;

		return function($self, $params, $chain) use ($config) {
			$params += array('timestamp' => strtotime('now'));
			$key = $config['key'];
			$key = is_callable($key) ? $key($params) : String::insert($key, $params);
			Writer::write($config['config'], $key, $params['message'], $config['expiry']);
		};
	}
}

?>