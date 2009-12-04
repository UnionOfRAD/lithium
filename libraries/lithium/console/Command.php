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
 * The base class to inherit when writing console scripts in Lithium.
 */
class Command extends \lithium\core\Object {

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
	 * Auto configuration
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
			'request' => null, 'response' => array(), 'classes' => array()
		);
		$config += $defaults;
		parent::__construct($config);
	}

	/**
	 * Initializer.  Populates the `response` property with a new instance of the `Response`
	 * class passing it configuration and assignes the values from named parameters of the
	 * request (if applicable) to properties of the command.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		$this->request = $this->_config['request'];
		$this->response = new $this->_classes['response']($this->_config['response']);

		if ($this->request) {
			foreach ((array)$this->request->params['named'] as $key => $param) {
				$this->{$key} = $param;
			}
		}
	}

	/**
	 * Base method, shows help. Override in subclasses as needed.
	 *
	 * @return void
	 */
	public function run() {
		$this->help();
		return false;
	}

	/**
	 * Called by the Dispatcher class to invoke an action.
	 *
	 * @param string $action
	 * @param array $passed
	 * @param array $options
	 * @return object The response object associated with this command.
	 * @see lithium\console\Dispatcher
	 * @see lithium\console\Response
	 * @todo Implement proper exception catching/throwing
	 * @todo Implement filters
	 */
	public function __invoke($action, $passed = array(), $options = array()) {
		try {
			$result = $this->invokeMethod($action, $passed);

			if (is_int($result)) {
				$this->response->status = $result;
			} elseif ($result || $result === null) {
				$this->response->status = 0;
			} else {
				$this->response->status = 1;
			}
		} catch (Exception $e) {
			$this->response->status = 1;
		}
		return $this->response;
	}

	/**
	 * Writes string to output stream.
	 *
	 * @param string $str
	 * @param integer $newlines
	 * @return integer|void
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
	 * Writes string to error stream.
	 *
	 * @param string $str
	 * @param integer $newlines
	 * @return integer|void
	 */
	public function error($str = null, $newlines = 1) {
		if (is_array($str)) {
			foreach ($str as $string) {
				$this->error($string, $newlines);
			}
			return;
		}
		if ($newlines) {
			$str = $str . str_pad("\n", $newlines, "\n");
		}
		return $this->response->error($str);
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
		$this->out($text);
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
	 * Add new lines to output stream.
	 *
	 * @param integer $number
	 * @return integer
	 */
	public function nl($number = 1) {
		return $this->out(null, $number);
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
	 * Stop execution with by exiting the script.
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
				$this->err($message);
			}
		}
		exit($status);
	}

	/**
	 * Show help generated from the documented code of the command.
	 *
	 * @return boolean
	 */
	public function help() {
		$parent = new ReflectionClass("\lithium\console\Command");
		$class = new ReflectionClass(get_class($this));
		$template = $class->newInstance();

		$filter = function($prop) {
			return $prop->isPublic() && !preg_match('/^[A-Z]/', $prop->getName());
		};
		$properties = array_diff($class->getProperties(), $parent->getProperties());
		$properties = array_filter($properties, $filter);

		$params = array();

		foreach ($properties as $property) {
			$name = str_replace('_', '-', Inflector::underscore($property->getName()));
			$default = $property->getValue($template);

			$comment = Docblock::comment($property->getDocComment());
			$description = str_replace("\n", ' ', $comment['description']);
			$type = isset($comment['tags']['var']) ? strtok($comment['tags']['var'], ' ') : null;

			$usage = $type == 'boolean' ? "--{$name}" : "--{$name}=" . strtoupper($name);

			$params[] = compact('name', 'default', 'description', 'type', 'usage');
		}

		$this->out('USAGE');
		$this->out(sprintf("    li3 %s%s [ARGS]",
			$this->request->params['command'] ?: 'COMMAND',
			array_reduce($params, function($a, $b) { return "{$a} {$b['usage']}"; })
		));

		$this->nl();
		$this->out('DESCRIPTION');
		$comment = Docblock::comment($class->getDocComment());
		$this->out("    {$comment['description']}");

		if ($params) {
			$this->nl();
			$this->out('OPTIONS');

			foreach ($params as $param) {
				$this->out("    {$param['usage']}");

				if ($param['description']) {
					$this->out("        {$param['description']}");
				}
				$this->nl();
			}
		}
		if (!$this->request->params['command']) {
			$this->nl();
			$this->out('COMMANDS');
			$commands = Libraries::locate('command', null, array('recursive' => false));
			$commands = array_unique($commands);

			foreach ($commands as $command) {
				$class = new ReflectionClass($command);
				$comment = Docblock::comment($class->getDocComment());
				$command = explode('\\', $command);

				$this->out("    " .	Inflector::underscore(array_pop($command)));
				$this->out("        " . str_replace("\n", ' ', $comment['description']));
			}
			$this->nl();
			$this->out('See `li3 COMMAND help` for more information on a specific command.');
		}
		return true;
	}
}

?>