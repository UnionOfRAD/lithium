<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace lithium\util;

abstract class Socket extends \lithium\core\Object {
	
	protected $_resource = null;
	
	public function __construct($config) {
		$defaults = array(
			'persistent' => false,
			'protocol'   => 'tcp',
			'host'       => 'localhost',
			'login'      => 'root',
			'password'   => '',
			'port'       => 80,
			'timeout'    => 30
		);
		parent::__construct((array)$config + $defaults);
	}
	
	abstract public function open();
	
	abstract public function close();
	
	abstract public function eof();
	
	abstract public function read();
	
	abstract public function write($data);
	
	abstract public function timeout($time);
	
	abstract public function encoding($charset);
	
	public function __destruct() {
		$this->close();
	}
	
	public function resource() {
		return $this->_resource;
	}	
}