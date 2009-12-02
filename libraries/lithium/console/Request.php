<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console;

/**
 * Holds current request from console
 *
 *
 **/
class Request extends \lithium\core\Object {

	/**
	 * Arguments from the console
	 *
	 * @var array
	 **/
	public $args = array();

	/**
	 * Params from router
	 *
	 * @var array
	 **/
	public $params = array(
		'command' => null, 'action' => 'run',
		'passed' => array(), 'named' => array()
	);

	/**
	 * Input stream, STDIN
	 *
	 * @var stream
	 **/
	public $input = null;

	/**
	 * Enviroment variables
	 *  - pwd path to where script is running
	 *  - working current directory
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
	 * Construct Request object
	 *
	 * @param array $config
	 *              - args array
	 *               [default] empty
	 *              - env array
	 *               [default] working => current working directory
	 *              - input stream
	 *
	 * @return void
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
		$this->_env += (array)$_SERVER + (array)$_ENV;
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
	 * get environment variabels
	 *
	 * @param string $key
	 * @return void
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
	 * Return input
	 *
	 * @return void
	 *
	 **/
	public function input() {
		return fgets($this->input);
	}

	/**
	 * Destructor to close streams
	 *
	 * @return void
	 *
	 **/
	public function __destruct() {
		fclose($this->input);
	}
}
?>