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
namespace lithium\console;

/**
 * Holds current request from console
 *
 * @package lithium.console
 *
 **/
class Response extends \lithium\core\Object {

	/**
	 * A Request object
	 *
	 * @var lithium\console\Request
	 **/
	public $request;

	/**
	 * Output stream, STDOUT
	 *
	 * @var stream
	 **/
	public $output = null;

	/**
	 * Error stream, STDERR
	 *
	 * @var stream
	 **/
	public $error = null;

	/**
	 * Construct Request object
	 *
	 * @param array $config
	 *              - request object lithium\console\Request
	 *              - output stream
	 *              _ error stream
	 * @access public
	 * @return void
	 *
	 **/
	public function __construct($config = array()) {
		$defaults = array(
			'request' => null, 'output' => null, 'error' => null
		);
		$config += $defaults;

		if (!empty($config['request'])) {
			$this->request = $config['request'];
		}

		$this->output = $config['output'];
		if (!is_resource($this->output)) {
			$this->output = fopen('php://stdout', 'r');;
		}

		$this->error = $config['error'];
		if (!is_resource($this->error)) {
			$this->error = fopen('php://stderr', 'r');;
		}
		parent::__construct($config);
	}

	/**
	 * Writes string to output stream
	 *
	 * @param string $string
	 * @return mixed
	 */
	public function output($string) {
		return fwrite($this->output, $string);
	}

	/**
	 * Writes string to error stream
	 *
	 * @param string $str
	 * @return mixed
	 */
	public function error($string) {
		return fwrite($this->error, $string);
	}

	/**
	 * Destructor to close streams
	 *
	 * @return void
	 *
	 **/
	public function __destruct() {
		fclose($this->output);
		fclose($this->error);
	}
}
?>