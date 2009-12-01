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
	 * Enviroment variables
	 *  - pwd path to where script is running
	 *  - working current directory
	 *
	 * @var array
	 **/
	public $env = array();

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
	 * Construct Request object
	 *
	 * @param array $config
	 *              - init boolean runs _init method
	 *               [default] false
	 *              - args array
	 *               [default] empty
	 *              - env array
	 *               [default] working => current working directory
	 *              - input stream
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$config += array(
			'init' => false,
			'argv' => array(),
			'args' => array(),
			'env' => array(),
			'input' => null,
		);

		if (!empty($_SERVER['argv'])) {
			$this->args += $_SERVER['argv'];
		}
		$this->args += $config['argv'];

		$this->env['working'] = getcwd() ?: null;
		$this->env['command'] = array_shift($this->args);

		$this->args = $config['args'] + $this->args;
		$this->env = $config['env'] + $this->env;
		$this->input = $config['input'];

		if (!is_resource($this->input)) {
			$this->input = fopen('php://stdin', 'r');
		}
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