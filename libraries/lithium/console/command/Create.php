<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command;

use \lithium\core\Libraries;
use \lithium\util\String;

/**
 * The `create` command allows you to rapidly develop your models, views, controllers, and tests
 * by generating the minimum code necessary to test and run your application.
 *
 */
class Create extends \lithium\console\Command {

	/**
	 * Controls the interactive nature of the command.
	 * When true, the command will ask questions and expect answers to generate the result.
	 * When false, the command will do its best to determine the result to generate.
	 *
	 * @var boolean
	 */
	public $i = false;

	/**
	 * Name of library to use
	 *
	 * @var string
	 */
	public $library = null;

	/**
	 * The name of the template to use to generate the file. This allows you to add a custom
	 * template to be used in place of the core template for each command. Place templates in
	 * `<library>\extensions\command\create\template`.
	 *
	 * @var string
	 */
	public $template = null;

	/**
	 * Holds library data from `\lithium\core\Libraries::get()`
	 *
	 * @var array
	 */
	protected $_library = array();

	/**
	 * Class initializer. Parses template and sets up params that need to be filled.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		$this->template = (!$this->template && !empty($this->request->args[0]))
			? $this->request->args[0] : null;
		$defaults = array('prefix' => null, 'path' => null);
		$this->library = $this->library ?: Libraries::get(true);
		$this->_library = (array) Libraries::get($this->library) + $defaults;
	}

	/**
	 * Magic method to call the appropriate sub-command and method.
	 *
	 * @param string $command The sub-command name. example: Model, Controller, Test
	 * @param string $params
	 * @return void
	 */
	public function __call($command, $params = array()) {
		if (!isset($this->_commands[$command])) {
			$class = Libraries::locate('command.create', $command);
			if (!$class) {
				$this->error("{$command} not found.");
				return false;
			}
			$this->_commands[$command] = new $class(array(
				'request' => $this->request->shift(2), 'classes'=> $this->_classes
			));
		}
		$command = $this->_commands[$command];
		$method = "_" . array_shift($params);

		if (!method_exists($command, $method)) {
			return null;
		}
		return $command->invokeMethod($method, $params);
	}

	/**
	 * Run the create command. Takes `$command` and delegates to `$command::$method`
	 *
	 * @param string $command
	 * @param string $method
	 * @return void
	 */
	public function run($command = null) {
		if (!$command || $this->i) {
			return $this->interactive();
		}
		$data = array();
		$params = $this->params($command);

		foreach ($params as $i => $param) {
			if (!$data[$param] = $this->{$command}($param)) {
				$data[$param] = !empty($this->request->args[$i]) ? $this->request->args[$i] : null;
			}
		}
		var_Dump($data);
		if ($this->_save($this->template, $data)) {
			$this->out("{$data['class']} created in {$data['namespace']}.");
			return true;
		}
		$this->error("{$command} could not be created.");
		return false;
	}

	/**
	 * [-i] Ask questions and use answers to create.
	 *
	 * @return void
	 */
	public function interactive() {

	}

	/**
	 * Parse a template to find available variables specified in `{:name}` format. Each variable
	 * corresponds to a method in the sub command. For example, a `{:namespace}` variable will
	 * call the namespace method in the model command when `li3 create model Post` is called.
	 *
	 * @param string $template
	 * @return array
	 */
	protected function params($template = null) {
		$contents = $this->_template($template);
		if (empty($contents)) {
			return array();
		}
		preg_match_all('/(?:\{:(?P<params>[^}]+)\})/', $contents, $keys);

		if (!empty($keys['params'])) {
			return array_values(array_unique($keys['params']));
		}
		return array();
	}

	/**
	 * Returns the contents of the template.
	 *
	 * @param string $name the name of the template
	 * @return string
	 */
	protected function _template($name = null) {
		$name = $this->template ? $this->template : $name;
		$file = Libraries::locate('command.create.template', $name, array(
			'filter' => false, 'type' => 'file', 'suffix' => '.txt.php',
		));
		if (!$file || is_array($file)) {
			return false;
		}
		return file_get_contents($file);
	}

	/**
	 * Get the namespace.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function _namespace($name = null) {
		$nameToSpace = array(
			'model' => 'models', 'view' => 'views', 'controller' => 'controllers',
			'command' => 'extensions.command', 'adapter' => 'extensions.adapter',
			'helper' => 'extensions.helper'
		);
		if (isset($nameToSpace[$name])) {
			$name = $nameToSpace[$name];
		}
		$name = str_replace('.', '\\', $name);
		return $this->_library['prefix'] . $name;
	}

	/**
	 * Save a template with the current params. Writes file to `Create::$path`.
	 *
	 * @param string $template
	 * @param string $params
	 * @return boolean
	 */
	protected function _save($template, $params = array()) {
		$contents = $this->_template($template);
		$result = String::insert($contents, $params);

		if (!empty($this->_library['path'])) {
			$path = $this->_library['path'] . str_replace(
				array('\\', $this->library), array('/',''),
				"\\{$params['namespace']}\\{$params['class']}"
			);
			$file = str_replace('//', '/', "{$path}.php");
			$directory = dirname($file);

			if (!is_dir($directory)) {
				if (!mkdir($directory, 0755, true)) {
					return false;
				}
			}
			return file_put_contents($file, "<?php\n\n{$result}\n\n?>");
		}
		return false;
	}
}

?>