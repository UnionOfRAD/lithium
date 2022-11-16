<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console;

use Exception;
use lithium\core\Libraries;
use lithium\console\command\Help;
use lithium\core\AutoConfigurable;

/**
 * All Commands to be run from the Lithium console must extend this class.
 *
 * The `run` method is automatically called if it exists. Otherwise, if a method does not exist
 * the `Help` command will be run.
 *
 * ```sh
 * li3 example
 * li3 example --format=json
 * ```
 *
 */
class Command {

	use AutoConfigurable;

	/**
	 * A Request object.
	 *
	 * @see lithium\console\Request
	 * @var object
	 */
	public $request;

	/**
	 * A Response object.
	 *
	 * @see lithium\console\Response
	 * @var object
	 */
	public $response;

	/**
	 * Surpresses all output except errors.
	 *
	 * @var boolean
	 */
	public $silent = false;

	/**
	 * Enables plain output by i.e. disabling colors. Useful when piping into other commands.
	 *
	 * @var boolean
	 */
	public $plain = false;

	/**
	 * Shows help for the command. Alternatively `-h` can be used.
	 *
	 * @var boolean
	 */
	public $help = false;

	/**
	 * Dynamic dependencies.
	 *
	 * @var array
	 */
	protected $_classes = [
		'response' => 'lithium\console\Response'
	];

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'request'` _object|null_
	 *        - `'response'` _array_
	 *        - `'classes'` _array_
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['request' => null, 'response' => [], 'classes' => $this->_classes];
		$this->_autoConfig($config + $defaults, ['classes' => 'merge']);
		$this->_autoInit($config);
	}

	/**
	 * Command Initializer.
	 *
	 * Populates the `$response` property with a new instance of the `Response` class passing it
	 * configuration and assigns the values from named parameters of the request (if applicable) to
	 * properties of the command.
	 *
	 * @return void
	 */
	protected function _init() {
		$this->request = $this->_config['request'];

		if (!is_object($this->request) || !$this->request->params) {
			return;
		}
		$default = ['command' => null, 'action' => null, 'args' => null];
		$params = array_diff_key((array) $this->request->params, $default);

		foreach ($params as $key => $param) {
			if (property_exists($this, $key)) {
				$this->{$key} = $param;
			}
		}
		$this->response = $this->_config['response'];

		if (!is_object($this->response)) {
			$this->response = Libraries::instance(
				null,
				'response',
				$this->response + ['plain' => $this->plain],
				$this->_classes
			);
		}
	}

	/**
	 * Called by the `Dispatcher` class to invoke an action.
	 *
	 * @see lithium\console\Dispatcher
	 * @see lithium\console\Response
	 * @param string $action The name of the method to run.
	 * @param array $args The arguments from the request.
	 * @return object The response object associated with this command.
	 * @todo Implement filters.
	 */
	public function __invoke($action, $args = []) {
		try {
			$this->response->status = 1;
			$result = call_user_func_array([$this, $action], $args);

			if (is_int($result)) {
				$this->response->status = $result;
			} elseif ($result || $result === null) {
				$this->response->status = 0;
			}
		} catch (Exception $e) {
			$this->error($e->getMessage());
		}
		return $this->response;
	}

	/**
	 * Invokes the `Help` command.
	 *
	 * The invoked Help command will take over request and response objects of
	 * the originally invoked command. Thus the response of the Help command
	 * becomes the response of the original one.
	 *
	 * @return boolean
	 */
	protected function _help() {
		$help = new Help([
			'request' => $this->request,
			'response' => $this->response,
			'classes' => $this->_classes
		]);
		return $help->run(get_class($this));
	}

	/**
	 * Writes a string to the output stream.
	 *
	 * @param string|array $output The string or an array of strings to write.
	 * @param mixed $options When passed an integer or boolean it is used as the number of
	 *                       of new lines, when passed a string it is interpreted as style
	 *                       to use otherwise when an array following options are available:
	 *                       - `'nl'` _integer|boolean_: number of new lines to add at the
	 *                          end. `false` to disable adding a newline.
	 *                       - `'style'` _string_: the style name to wrap around the output.
	 * @return integer
	 */
	public function out($output = null, $options = ['nl' => 1]) {
		if ($this->silent) {
			return;
		}
		return $this->_response('output', $output, $options);
	}

	/**
	 * Writes a string to error stream.
	 *
	 * @param string|array $error The string or an array of strings to write.
	 * @param mixed $options When passed an integer or boolean it is used as the number of
	 *                       of new lines, when passed a string it is interpreted as style
	 *                       to use otherwise when an array following options are available:
	 *                       - `'nl'` _integer|boolean_: number of new lines to add at the
	 *                          end. `false` to disable adding a newline.
	 *                       - `'style'` _string_: the style name to wrap around the output.
	 * @return integer
	 */
	public function error($error = null, $options = ['nl' => 1]) {
		return $this->_response('error', $error, $options);
	}

	/**
	 * Handles input. Will continue to loop until `$options['quit']` or
	 * result is part of `$options['choices']`.
	 *
	 * @param string $prompt
	 * @param array $options
	 * @return string|boolean Returns the result of the input data. If the input is
	 *         equal to the `quit` option boolean `false` is returned.
	 */
	public function in($prompt = null, array $options = []) {
		$defaults = ['choices' => null, 'default' => null, 'quit' => 'q'];
		$options += $defaults;
		$choices = null;

		if (is_array($options['choices'])) {
			$choices = '(' . implode('/', $options['choices']) . ')';
		}
		$default = $options['default'] ? "[{$options['default']}] " : '';

		do {
			$this->out("{$prompt} {$choices} \n {$default}> ", 0);
			$result = trim($this->request->input());
		} while (
			!empty($options['choices']) &&
			!in_array($result, $options['choices'], true) &&
			(empty($options['quit']) || $result !== $options['quit']) &&
			(!$options['default'] || $result !== '')
		);

		if ($result == $options['quit']) {
			return false;
		}

		if ($options['default'] !== null && $result == '') {
			return $options['default'];
		}
		return $result;
	}

	/**
	 * Writes a header to the output stream. In addition to the actual text,
	 * horizontal lines before and afterwards are written. The lines will have
	 * the same length as the text. This behavior can be modified by providing
	 * the length of lines as a second paramerter.
	 *
	 * Given the text `'Lithium'` this generates following output:
	 *
	 * ```
	 * -------
	 * Lithium
	 * -------
	 * ```
	 *
	 * @param string $text The heading text.
	 * @param integer $line The length of the line. Defaults to the length of text.
	 * @return void
	 */
	public function header($text, $line = null) {
		if (!$line) {
			$line = strlen($text);
		}
		$this->hr($line);
		$this->out($text, 'heading');
		$this->hr($line);
	}

	/**
	 * Writes rows of columns.
	 *
	 * This method expects asceding integer values as the keys, which map to the appropriate
	 * columns. Currently, there is no special "header" option, but you can define them for your
	 * own.
	 *
	 * Example Usage:
	 *
	 * ```
	 * $output = [
	 *     ['Name', 'Age'],
	 *     ['----', '---'],
	 * ];
	 * foreach($users as $user) {
	 *     $output[] = [$user->name, $user->age];
	 * }
	 * $this->columns($output);
	 * ```
	 *
	 * Would render something similar to:
	 *
	 * ```
	 * Name       Age
	 * ----       ---
	 * Jane Doe   22
	 * Foo Bar    18
	 * ```
	 *
	 * This method also calculates the needed space between the columns. All option params given
	 * also get passed down to the `out()` method, which allow custom formatting. Passing something
	 * like `$this->columns($output, ['style' => 'red]` would print the table in red.
	 *
	 * @see lithium\console\Response::styles()
	 * @param array $rows The rows to print, with each column as an array element.
	 * @param array $options Optional params:
	 *      - separator : Different column separator, defaults to `\t`
	 *      - style : the style name to wrap around the columns output
	 * @return void
	 */
	public function columns($rows, $options = []) {
		$defaults = ['separator' => "\t", "error" => false];
		$options += $defaults;
		$lengths = array_reduce($rows, function($columns, $row) {
			foreach ((array) $row as $key => $val) {
				if (!isset($columns[$key]) || strlen($val) > $columns[$key]) {
					$columns[$key] = strlen($val);
				}
			}
			return $columns;
		});
		$rows = array_reduce($rows, function($rows, $row) use ($lengths, $options) {
			$text = '';
			foreach ((array) $row as $key => $val) {
				$text = $text . str_pad($val, $lengths[$key]) . $options['separator'];
			}
			$rows[] = $text;
			return $rows;
		});
		if ($options['error']) {
			$this->error($rows, $options);
			return;
		}
		$this->out($rows, $options);
	}

	/**
	 * Add newlines ("\n") a given number of times and return them in a single string.
	 *
	 * @param integer $number The number of new lines to fill into a string.
	 * @return string
	 */
	public function nl($number = 1) {
		return str_repeat("\n", $number);
	}

	/**
	 * Adds a horizontal line to output stream.
	 *
	 * @param integer $length The length of the line, defaults to 80.
	 * @param integer $newlines How many new lines to print afterwards, defaults to 1.
	 * @return integer
	 */
	public function hr($length = 80, $newlines = 1) {
		return $this->out(str_repeat('-', $length), $newlines);
	}

	/**
	 * Clears the entire screen.
	 *
	 * @return void
	 */
	public function clear() {
		passthru(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? 'cls' : 'clear');
	}

	/**
	 * Stop execution, by exiting the script.
	 *
	 * @param integer $status Numeric value that will be used on `exit()`.
	 * @param string|null $message An optional message that will be written to the stream.
	 * @return void
	 */
	public function stop($status = 0, $message = null) {
		if ($message) {
			($status == 0) ? $this->out($message) : $this->error($message);
		}
		exit($status);
	}

	/**
	 * Handles the response that is sent to the stream.
	 *
	 * @param string $type The stream either output or error.
	 * @param string|array $string The message to render.
	 * @param mixed $options When passed an integer or boolean it is used as the number of
	 *                       of new lines, when passed a string it is interpreted as style
	 *                       to use otherwise when an array following options are available:
	 *                       - `'nl'` _integer|boolean_: number of new lines to add at the
	 *                          end. `false` to disable adding a newline.
	 *                       - `'style'` _string_: the style name to wrap around the output.
	 * @return void
	 */
	protected function _response($type, $string, $options) {
		$defaults = ['nl' => 1, 'style' => null];

		if (!is_array($options)) {
			if (is_bool($options)) {
				$options = ['nl' => (integer) $options];
			} elseif (is_int($options)) {
				$options = ['nl' => $options];
			} elseif (is_string($options)) {
				$options = ['style' => $options];
			} else {
				$options = [];
			}
		}
		$options += $defaults;

		if (is_array($string)) {
			$method = ($type == 'error' ? $type : 'out');
			foreach ($string as $out) {
				$this->{$method}($out, $options);
			}
			return;
		}
		if ($options['style'] !== null) {
			$string = "{:{$options['style']}}{$string}{:end}";
		}
		if ($options['nl']) {
			$string = $string . $this->nl((integer) $options['nl']);
		}
		return $this->response->{$type}($string);
	}

	/**
	 * Exit immediately. Primarily used for overrides during testing.
	 *
	 * @param integer|string $status integer range 0 to 254, string printed on exit
	 * @return void
	 */
	protected function _stop($status = 0) {
		exit($status);
	}
}

?>