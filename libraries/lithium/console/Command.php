<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console;

use \Exception;
use \lithium\console\command\Help;

/**
 * The base class to inherit when writing console scripts in Lithium.
 */
class Command extends \lithium\core\Object {

	/**
	 * If -h or --help param exists a help screen will be returned.
	 * Similar to running `li3 help COMMAND`.
	 *
	 * @var boolean
	 */
	public $help = false;

	/**
	 * A Request object.
	 *
	 * @var object
	 * @see lithium\console\Request
	 */
	public $request;

	/**
	 * A Response object.
	 *
	 * @var object
	 * @see lithium\console\Response
	 */
	public $response;

	/**
	 * Dynamic dependencies.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'response' => '\lithium\console\Response'
	);

	/**
	 * Auto configuration.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('classes' => 'merge');

	/**
	 * Constructor.
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'request' => null, 'response' => array(), 'classes' => $this->_classes
		);
		$config += $defaults;
		parent::__construct($config);
	}

	/**
	 * Initializer.  Populates the `response` property with a new instance of the `Response`
	 * class passing it configuration and assigns the values from named parameters of the
	 * request (if applicable) to properties of the command.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		$this->request = $this->_config['request'];
		$this->response = new $this->_classes['response']($this->_config['response']);

		if (!empty($this->request->params)) {
			$params = (array) array_diff_key(
				$this->request->params, array('command' => null, 'action' => null, 'args' => null)
			);
			foreach ($params as $key => $param) {
				$this->{$key} = $param;
			}
		}
	}

	/**
	 * Called by the Dispatcher class to invoke an action.
	 *
	 * @param string $action name of the method to run
	 * @param array $args the args from the request
	 * @param array $options
	 * @return object The response object associated with this command.
	 * @see lithium\console\Dispatcher
	 * @see lithium\console\Response
	 * @todo Implement proper exception catching/throwing.
	 * @todo Implement filters.
	 */
	public function __invoke($action, $args = array(), $options = array()) {
		try {
			$this->response->status = 1;
			$result = $this->invokeMethod($action, $args);
			if (is_int($result)) {
				$this->response->status = $result;
			} elseif ($result || $result === null) {
				$this->response->status = 0;
			}
		} catch (Exception $e) {
			$this->response->status = 1;
		}
		return $this->response;
	}

	/**
	 * Writes string to output stream.
	 *
	 * @param string $output
	 * @param integer|string|array $options
	 *        integer as the number of new lines.
	 *        string as the style
	 *        array as :
	 *        - nl : number of new lines to add at the end
	 *        - style : the style name to wrap around the
	 * @return integer|void
	 */
	public function out($output = null, $options = array('nl' => 1)) {
		$options = is_int($options) ? array('nl' => $options) : $options;
		return $this->_response('output', $output, $options);
	}

	/**
	 * Writes string to error stream.
	 *
	 * @param string $error
	 * @param integer|string|array $options
	 *        integer as the number of new lines.
	 *        string as the style
	 *        array as :
	 *        - nl : number of new lines to add at the end
	 *        - style : the style name to wrap around the
	 * @return integer|void
	 */
	public function error($error = null, $options = array('nl' => 1)) {
		return $this->_response('error', $error, $options);
	}

	/**
	 * Handles input. Will continue to loop until `$options['quit']` or
	 * result is part of `$options['options']`.
	 *
	 * @param string $prompt
	 * @param string $options
	 * @return string
	 */
	public function in($prompt = null, $options = array()) {
		$defaults = array('choices' => null, 'default' => null, 'quit' => 'q');
		$options += $defaults;

		$choices = null;
		if (is_array($options['choices'])) {
			$choices = '(' . implode('/', $options['choices']) . ')';
		}

		if ($options['default'] == null) {
			$this->out("{$prompt} {$choices} \n > ", false);
		} else {
			$this->out("{$prompt} {$choices} \n [{$options['default']}] > ", false);
		}

		$result = null;
		do  {
			$result = trim($this->request->input());
		} while (
			$result == null && !empty($options['quit']) && $result != $options['quit']
			&& !empty($options['options']) && array_search($result, $options['options'])
		);

		if ($options['default'] != null && empty($result)) {
			return $options['default'] ;
		}
		return $result;
	}

	/**
	 * Add text with horizontal line before and after stream.
	 *
	 * @param string $text
	 * @param integer $line
	 * @return void
	 */
	public function header($text, $line = 80) {
		$this->hr($line);
		$this->out($text, 1, 'heading1');
		$this->hr($line);
	}

	/**
	 * Writes rows of columns.
	 *
	 * @param array $rows
	 * @param string $separator Defaults to `"\t"`.
	 * @return void
	 */
	public function columns($rows, $separator = "\t") {
		$lengths = array_reduce($rows, function($columns, $row) {
			foreach ((array) $row as $key => $val) {
				if (!isset($columns[$key]) || strlen($val) > $columns[$key]) {
					$columns[$key] = strlen($val);
				}
			}
			return $columns;
		});
		$rows = array_reduce($rows, function($rows, $row) use ($lengths, $separator) {
			$text = '';
			foreach ((array) $row as $key => $val) {
				$text = $text . str_pad($val, $lengths[$key]) . $separator;
			}
			$rows[] = $text;
			return $rows;
		});
		$this->out($rows);
	}

	/**
	 * Add newlines ("\n") to output stream.
	 *
	 * @param integer $number
	 * @return integer
	 */
	public function nl($number = 1) {
		return str_repeat("\n", $number);
	}

	/**
	 * Add horizontal line to output stream
	 *
	 * @param integer $length
	 * @param integer $newlines
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
		passthru(substr(PHP_OS, 0, 3) == 'WIN' ? 'cls' : 'clear');
	}

	/**
	 * Stop execution, by exiting the script.
	 *
	 * @param integer $status
	 * @param boolean $message
	 * @return void
	 */
	public function stop($status = 0, $message = null) {
		if (!is_null($message)) {
			if ($status == 0) {
				$this->out($message);
			} else {
				$this->error($message);
			}
		}
		exit($status);
	}

	/**
	 * Show help generated from the documented code of the command.
	 *
	 * @return boolean
	 */
	protected function _help() {
		$help = new Help($this->_config);
		$result = $help->run(get_class($this));
		$this->response = $help->response;
		return $result;
	}

	/**
	 * Handles the response that is sent to the stream.
	 *
	 * @param string $type the stream either output or error
	 * @param string $string the message to render
	 * @param integer|string|array $options
	 *        integer as the number of new lines.
	 *        string as the style
	 *        array as :
	 *        - nl : number of new lines to add at the end
	 *        - style : the style name to wrap around the
	 * @return void
	 */
	protected function _response($type, $string, $options) {
		$defaults = array('nl' => 1, 'style' => null);
		if (!is_array($options)) {
			if (!$options || is_int($options)) {
				$options = array('nl' => $options);
			} else if (is_string($options)) {
				$options = array('style' => $options);
			} else {
				$options = array();
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
		extract($options);

		if($style !== null) {
			$string = "{:{$style}}{$string}{:end}";
		}
		if ($nl) {
			$string = $string . $this->nl($nl);
		}
		return $this->response->{$type}($string);
	}
}

?>