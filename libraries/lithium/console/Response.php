<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console;

/**
 * Holds current request from console
 *
 *
 **/
class Response extends \lithium\core\Object {

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
	 * Status code, most often used for setting an exit status.
	 *
	 * It should be expected that only status codes in the range of 0-255
	 * can be properly evalutated.
	 *
	 * @var integer
	 * @see lithium\console\Command
	 */
	public $status = 0;

	/**
	 * Construct Request object
	 *
	 * @param array $config
	 *              - request object lithium\console\Request
	 *              - output stream
	 *              _ error stream
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('output' => null, 'error' => null);
		$config += $defaults;

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
	 * @param string $string
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