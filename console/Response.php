<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console;

use lithium\util\Text;
use lithium\core\AutoConfigurable;

/**
 * The `Response` class is used by other console classes to generate output. It contains stream
 * resources for writing output and errors, as well as shell coloring information, and the response
 * status code for the currently-executing command.
 */
class Response {

	use AutoConfigurable;

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
	 * Disables color output. Useful when piping command output
	 * into other commands. Colors are by default enabled.
	 *
	 * @var boolean
	 * @see lithium\console\Response::styles()
	 */
	public $plain = false;

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'output'` _resource|null_
	 *        - `'error'` _resource|null_
	 *        - `'color'` _boolean_ By default `true`.
	 * @return void
	 */
	public function __construct($config = []) {
		$defaults = ['output' => null, 'error' => null, 'plain' => false];
		$config += $defaults;

		$this->output = $config['output'];

		if (!is_resource($this->output)) {
			$this->output = fopen('php://stdout', 'r');
		}

		$this->error = $config['error'];

		if (!is_resource($this->error)) {
			$this->error = fopen('php://stderr', 'r');
		}
		$this->plain = $config['plain'];

		$this->_autoConfig($config, []);
		$this->_autoInit($config);
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
	 * Handles styling output. Uses ANSI escape sequences for colorization. These
	 * may not always be supported (i.e. on Windows).
	 *
	 * @param array|boolean $styles
	 * @return array
	 */
	public function styles($styles = []) {
		$defaults = [
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
		];
		if ($styles === false || $this->plain || strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			return array_combine(array_keys($defaults), array_pad([], count($defaults), null));
		}
		return $styles + $defaults;
	}
}

?>