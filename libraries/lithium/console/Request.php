<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console;

/**
 * The `Request` class reprents a console request and holds information about it's
 * environment as well as passed arguments.
 *
 * @see lithium\console\Dispatcher
 */
class Request extends \lithium\core\Object {

	/**
	 * Arguments for the request.
	 *
	 * @var array
	 */
	public $args = array();

	/**
	 * Parameters parsed from arguments.
	 *
	 * @var array
	 * @see lithium\console\Router
	 */
	public $params = array(
		'command' => null, 'action' => 'run', 'args' => array()
	);

	/**
	 * Input (STDIN).
	 *
	 * @var resource
	 */
	public $input;

	/**
	 * Enviroment variables.
	 *
	 * @var array
	 **/
	protected $_env = array();

	/**
	 * Auto configuration
	 *
	 * @var array
	 */
	protected $_autoConfig = array('env' => 'merge');

	/**
	 * Class Constructor
	 *
	 * @param array $config
	 */
	public function __construct($config = array()) {
		$defaults = array('args' => array(), 'input' => null);
		$config += $defaults;
		parent::__construct($config);
	}

	/**
	 * Initialize request object
	 *
	 * @return void
	 */
	protected function _init() {
		$this->_env += (array) $_SERVER + (array) $_ENV;
		$this->_env['working'] = getcwd() ?: null;
		$argv = (array) $this->env('argv');
		$this->_env['script'] = array_shift($argv);
		$this->args += $argv + (array) $this->_config['args'];
		$this->input = $this->_config['input'];

		if (!is_resource($this->_config['input'])) {
			$this->input = fopen('php://stdin', 'r');
		}
		parent::_init();
	}
	
	/**
	 * Allows request parameters to be accessed as object properties, i.e. `$this->request->action`
	 * instead of `$this->request->params['action']`.
	 *
	 * @param string $name The property name/parameter key to return.
	 * @return mixed Returns the value of `$params[$name]` if it is set, otherwise returns null.
	 * @see lithium\action\Request::$params
	 */
	public function __get($name) {
		if (isset($this->params[$name])) {
			return $this->params[$name];
		}
	}

	/**
	 * Get environment variables.
	 *
	 * @param string $key
	 * @return string|void
	 */
	public function env($key = null) {
		if (!empty($this->_env[$key])) {
			return $this->_env[$key];
		}
		if ($key === null) {
			return $this->_env;
		}
		return null;
	}

	/**
	 * Moves params up a level. Sets command to action, action to passed[0], and so on.
	 *
	 * @param integer $num how many times to shift
	 * @return self
	 */
	public function shift($num = 1) {
		for ($i = $num; $i > 1; $i--) {
			$this->shift(--$i);
		}
		$this->params['command'] = $this->params['action'];
		$this->params['action'] = array_shift($this->params['args']);
		return $this;
	}

	/**
	 * Reads a line from input.
	 *
	 * @return string
	 */
	public function input() {
		return fgets($this->input);
	}

	/**
	 * Return input
	 * Destructor. Closes input.
	 *
	 * @return void
	 */
	public function __destruct() {
		if ($this->input) {
			fclose($this->input);
		}
	}
}

?>