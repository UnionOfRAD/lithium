<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2015, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console;

use lithium\util\Text;

/**
 * The `Response` class is used by other console classes to generate output. It contains stream
 * resources for writing output and errors, as well as shell coloring information, and the response
 * status code for the currently-executing command.
 */
class Response extends \lithium\core\Object {

	/**
	 * Output stream, STDOUT
	 *
	 * @var resource
	 */
	public $output = null;

	/**
	 * Error stream, STDERR
	 *
	 * @var resource
	 */
	public $error = null;

	/**
	 * Don't show color on output
	 *
	 * @var boolean
	 */
	public $noColor = false;

	/**
	 * Status code, most often used for setting an exit status.
	 *
	 * It should be expected that only status codes in the range of 0-255
	 * can be properly evaluated.
	 *
	 * @var integer
	 * @see lithium\console\Command
	 */
	public $status = 0;

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'output'` _resource|null_
	 *        - `'error'` _resource|null_
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array('output' => null, 'error' => null, 'noColor' => false);
		$config += $defaults;

		$this->output = $config['output'];

		if (!is_resource($this->output)) {
			$this->output = fopen('php://stdout', 'r');
		}

		$this->error = $config['error'];

		if (!is_resource($this->error)) {
			$this->error = fopen('php://stderr', 'r');
		}

		$this->noColor = $config['noColor'];

		parent::__construct($config);
	}

	/**
	 * Writes string to output stream
	 *
	 * @param string $output
	 * @return mixed
	 */
	public function output($output) {
		return fwrite($this->output, Text::insert($output, $this->styles()));
	}

	/**
	 * Writes string to error stream
	 *
	 * @param string $error
	 * @return mixed
	 */
	public function error($error) {
		return fwrite($this->error, Text::insert($error, $this->styles()));
	}

	/**
	 * Destructor. Closes streams.
	 *
	 * @return void
	 */
	public function __destruct() {
		if ($this->output) {
			fclose($this->output);
		}
		if ($this->error) {
			fclose($this->error);
		}
	}

	/**
	 * Handles styling output.
	 *
	 * @param array|boolean $styles
	 * @return array
	 */
	public function styles($styles = array()) {
		$defaults = array(
			'end'    => "\033[0m",
			'black'  => "\033[0;30m",
			'red'    => "\033[0;31m",
			'green'  => "\033[0;32m",
			'yellow' => "\033[0;33m",
			'blue'   => "\033[0;34m",
			'purple' => "\033[0;35m",
			'cyan'   => "\033[0;36m",
			'white'  => "\033[0;37m",
			'heading' => "\033[1;36m",
			'option'  => "\033[0;35m",
			'command' => "\033[0;35m",
			'error'   => "\033[0;31m",
			'success' => "\033[0;32m",
			'bold'    => "\033[1m",
		);

		if ($styles === false || $this->noColor || strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			return array_combine(array_keys($defaults), array_pad(array(), count($defaults), null));
		}
		return $styles + $defaults;
	}
}

?>