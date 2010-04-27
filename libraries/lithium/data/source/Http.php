<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use \lithium\core\Libraries;

/**
 * Http class to access data sources using \lithium\net\http\Service.
 */
class Http extends \lithium\data\Source {

	/**
	 * Service connection
	 *
	 * @var object lithium\net\http\Service
	 */
	public $connection = null;

	/**
	 * The set of array keys which will be auto-populated in the object's protected properties from
	 * constructor parameters.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('classes' => 'merge');

	/**
	 * Fully-namespaced class references
	 *
	 * @var array
	 */
	protected $_classes = array(
		'service' => '\lithium\net\http\Service'
	);

	/**
	 * Is Connected?
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	/**
	 * Constructor
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct(array $config = array()) {
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
		$config = (array) $config + $defaults;

		$config['auth'] = array(
			'method' => $config['auth'],
			'username' => $config['login'],
			'password' => $config['password']
		);
		$this->_classes = $config['classes'] + $this->_classes;
		parent::__construct($config);
	}

	protected function _init() {
		$this->connection = new $this->_classes['service']($this->_config);
		parent::_init();
	}

	/**
	 * Pass properties to service connection
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		return $this->connection->{$property};
	}

	/**
	 * Pass methods to service connection.
	 *
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	public function __call($method, $params) {
		return $this->connection->invokeMethod($method, $params);
	}

	/**
	 * Connect to the data-source.
	 *
	 * @return boolean
	 */
	public function connect() {
		if (!$this->_isConnected && $this->connection->connect()) {
			$this->_isConnected = true;
		}
		return $this->_isConnected;
	}

	/**
	 * Disconnect from socket.
	 *
	 * @return boolean
	 */
	public function disconnect() {
		if ($this->_isConnected) {
			if ($this->connection->disconnect()) {
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
	 * Describe data source.
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
	 * @param object $query
	 * @param array $options
	 * @return void
	 */
	public function create($query, array $options = array()) {
		return $this->connection->post();
	}

	/**
	 * Read used by model to GET.
	 *
	 * @param object $query
	 * @param array $options
	 * @return string
	 */
	public function read($query, array $options = array()) {
		return $this->connection->get();
	}

	/**
	 * Update used by model to PUT.
	 *
	 * @param object $query
	 * @param array $options
	 * @return string
	 */
	public function update($query, array $options = array()) {
		return $this->connection->put();
	}

	/**
	 * Used by model to DELETE.
	 *
	 * @param object $query
	 * @param array $options
	 * @return string
	 */
	public function delete($query = null, array $options = array()) {
		return $this->connection->delete();
	}

	/**
	 * Defines or modifies the default settings of a relationship between two models.
	 *
	 * @param string $class
	 * @param string $type
	 * @param string $name
	 * @param array $options
	 * @return array Returns an array containing the configuration for a model relationship.
	 */
	public function relationship($class, $type, $name, array $options = array()) {
		return array();
	}
}

?>