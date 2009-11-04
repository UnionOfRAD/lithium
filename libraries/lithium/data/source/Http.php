<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use \lithium\core\Libraries;

/**
 * Http class to access data sources using Socket classes
 */
class Http extends \lithium\data\Source {

	protected $_autoConfig = array('classes' => 'merge');

	/**
	 * Fully-namespaced class references
	 *
	 * @var array
	 */
	protected $_classes = array(
		'service' => '\lithium\http\Service',
	);

	/**
	 * Service connection
	 *
	 * @var object lithium\http\Service
	 */
	protected $_connection = null;

	/**
	 * Is Connected?
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	/**
	 * Request Object
	 *
	 * @var object
	 */
	public $request =  null;

	/**
	 * Holds all parameters of the request
	 * Cast to object in the constructor
	 *
	 * @var object
	 */
	public $response = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'classes'    => array(),
			'adapter'    => null,
			'persistent' => false,
			'protocol'   => 'tcp',
			'host'       => 'localhost',
			'version'    => '1.1',
			'auth'       => 'Basic',
			'login'      => 'root',
			'password'   => '',
			'port'       => 80,
			'timeout'    => 1,
			'encoding'   => 'UTF-8'
		);
		$config = (array)$config + $defaults;

		$config['auth'] = array(
			'method' => $config['auth'],
			'username' => $config['login'],
			'password' => $config['password']
		);
		$this->_classes = $config['classes'] + $this->_classes;
		parent::__construct($config);
	}

	protected function _init() {
		$service = $this->_classes['service'];
		if (!class_exists($service)) {
			$service = Libraries::locate('http', $this->_classes['service']);
		}
		$this->_connection = new $service($this->_config);
	}

	public function __get($property) {
		return $this->_connection->{$property};
	}

	public function __set($proerty, $value) {
		return $this->_connection->{$property} = $value;
	}

	public function __call($method, $params) {
		return $this->_connection->invokeMethod($method, $params);
	}

	/**
	 * Connect to datasource
	 *
	 * @return boolean
	 */
	public function connect() {
		if (!$this->_isConnected && $this->_connection->connect()) {
			$this->_isConnected = true;
		}
		return $this->_isConnected;
	}

	/**
	 * Disconnect from socket
	 *
	 * @return boolean
	 */
	public function disconnect() {
		if ($this->_isConnected) {
			if ($this->_connection->disconnect()) {
				$this->_isConnected = false;
			}
		}
		return !$this->_isConnected;
	}

	/**
	 * entities
	 *
	 * @param object $class
	 * @return array
	 */
	public function entities($class = null) {
		return array();
	}

	/**
	 * Describe data source
	 *
	 * @param string $entity
	 * @param string $meta
	 * @return void
	 */
	public function describe($entity, $meta = array()) {
	}

	/**
	 * undocumented function
	 *
	 * @param object $record
	 * @param string $options
	 * @return void
	 */
	public function create($record, $options = array()) {
		return $this->_connection->post();
	}
	/**
	 * Read used by model to GET
	 *
	 * @param object query
	 * @param array options
	 * @return string
	 */
	public function read($query = array(), $options = array()) {
		return $this->_connection->get();
	}

	/**
	 * Update used by model to PUT
	 *
	 * @param object query
	 * @param array options
	 * @return string
	 */
	public function update($query, $options = array()) {
		return $this->_connection->put();
	}

	/**
	 * Used by model to DELETE
	 *
	 * @param object query
	 * @param array options
	 * @return string
	 */
	public function delete($query, $options = array()) {
		return $this->_connection->delete();
	}
}

?>