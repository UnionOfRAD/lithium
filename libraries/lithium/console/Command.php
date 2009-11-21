<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console;

use \Exception;
use \ReflectionClass;
use \lithium\util\Inflector;
use \lithium\util\reflection\Docblock;
use \lithium\core\Libraries;

/**
 * The base class to inherit when writing Console scripts in Lithium.
 *
 */
class Command extends \lithium\core\Object {

	/**
	 * A Request object
	 *
	 * @var lithium\console\Request
	 */
	public $request;

	/**
	 * A Response object
	 *
	 * @var lithium\console\Response
	 */
	public $response;

	/**
	 * classes used by Command
	 *
	 * @var string
	 */
	protected $_classes = array(
		'response' => '\lithium\console\Response'
	);

	/**
	 * Constrcutor
	 *
	 * @param array config
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'request' => null, 'response' => array(), 'classes' => array()
		);
		$config += $defaults;

		if (!empty($config['request'])) {
			$this->request = $config['request'];
		}

		if (!empty($config['classes'])) {
			$this->{'_' . $key} = (array)$config[$key] + $this->{'_' . $key};
		}

		parent::__construct($config);
	}

	public function _init() {
		$config = (array)$this->_config['response'] + array('request' => $this->request);
		$this->response = new $this->_classes['response']($config);
	}

	/**
	 * initialize callback
	 *
	 * @return void
	 */
	public function initialize() {

	}

	/**
	 * base method, shows list of available commands
	 * override in subclasses
	 *
	 * @return void
	 */
	public function run() {
		$this->header('Available Commands');
		$classes = array_unique(Libraries::locate('commands', null, array('recursive' => false)));
		foreach ($classes as $command) {
			$command = explode('\\', $command);
			$this->out(' - ' . Inflector::underscore(array_pop($command)));
		}
	}

	/**
	 * Called by the Dispatcher class to invoke an action
	 *
	 * @param string $action
	 * @param array $params
	 * @return object Returns the response object associated with this controller
	 * @todo Implement proper exception catching/throwing
	 * @todo Implement filters
	 */
	public function __invoke($action, $passed = array(), $options = array()) {
		$result = null;

		try {
			foreach ((array)$this->request->params['named'] as $key => $param) {
				$this->{$key} = $param;
			}
			$this->initialize();
			$result = $this->invokeMethod($action, $passed);
		} catch (Exception $e) {
			// See todo
		}
		return $result;
	}

	/**
	 * Writes string to output stream
	 *
	 * @param string $str
	 * @param integer $newlines
	 * @return boolean
	 */
	public function out($str = null, $newlines = 1) {
		if (is_array($str)) {
			foreach ($str as $string) {
				$this->out($string, $newlines);
			}
			return;
		}
		if ($newlines) {
			$str = $str . str_pad("\n", $newlines, "\n");
		}
		return $this->response->output($str);
	}

	/**
	 * Writes string to error stream
	 *
	 * @param string $str
	 * @param integer $newlines
	 * @return boolean
	 */
	public function err($str = null, $newlines = 1) {
		if (is_array($str)) {
			foreach ($str as $string) {
				$this->err($string, $newlines);
			}
			return;
		}
		if ($newlines) {
			$str = $str . str_pad("\n", $newlines, "\n");
		}
		return $this->response->error($str);
	}

	/**
	 * Handles input. Will continue to loop until
	 * options['quit'] or result is part of options['options']
	 *
	 * @param string $prompt
	 * @param string $options
	 * @param string $default
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
	 * Add text with horizontal line before and after stream
	 *
	 * @param integer $length
	 * @param integer $newlines
	 * @return string
	 */
	public function header($text, $line = 80) {
		$this->hr($line);
		$this->out($text);
		$this->hr($line);
	}

	/**
	 * Writes rows of columns
	 *
	 * @param array $rows
	 * @param string $separator (default "\t")
	 * @return string
	 */
	public function columns($rows, $separator = "\t") {
		$lengths = array_reduce($rows, function($columns, $row) {
			foreach ((array)$row as $key => $val) {
				if (!isset($columns[$key]) || strlen($val) > $columns[$key]) {
					$columns[$key] = strlen($val);
				}
			}
			return $columns;
		});
		$rows = array_reduce($rows, function($rows, $row) use ($lengths, $separator) {
			$text = '';
			foreach ((array)$row as $key => $val) {
				$text = $text . str_pad($val, $lengths[$key]) . $separator;
			}
			$rows[] = $text;
			return $rows;
		});
		$this->out($rows);
	}

	/**
	 * Add new lines to output stream
	 *
	 * @param integer $number
	 * @return string
	 */
	public function nl($number = 1) {
		return $this->out(null, $number);
	}

	/**
	 * Add horizontal line to output stream
	 *
	 * @param integer $length
	 * @param integer $newlines
	 * @return string
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
	 * Stop execution with exit
	 *
	 * @param integer $status
	 * @param boolean $message
	 * @return string
	 */
	public function stop($status = 0, $message = null) {
		if (!is_null($message)) {
			if ($status == 0) {
				$this->out($message);
			} else {
				$this->err($message);
			}
		}
		exit($status);
	}

	/**
	 * Will show basic help for the command
	 *
	 * @return void
	 */
	public function help() {
		$parent = new ReflectionClass("\lithium\console\Command");
		$class = new ReflectionClass(get_class($this));

		$params = array();
		$template = $class->newInstance();
		$properties = array_diff($class->getProperties(), $parent->getProperties());
		$propertyFilter = function($prop) {
			return $prop->isPublic() && !preg_match('/^[A-Z]/', $prop->getName());
		};

		foreach ((array)array_filter($properties, $propertyFilter) as $property) {
			$hint = null;
			$val = $property->getValue($template);

			if (!is_bool($val)) {
				$hint = '=val';
				$comment = Docblock::comment($property->getDocComment());
				if (isset($comment['tags']['var'])) {
					$hint = '=' . strtoupper($comment['tags']['var']);
				}
			}
			$name = str_replace('_', '-', Inflector::underscore($property->getName()));
			$params[] = sprintf('[--%s%s]', $name, $hint);
		}

		// Show parameters as well
		$className = explode("\\", $class->getName());
		$command = array_pop($className);
		$this->out(sprintf(
			'usage: lithium %s %s', $command, join(' ', $params)
		), 2);

		$comment = Docblock::comment($class->getDocComment());
		$this->out($comment['description']);
	}
}

?>