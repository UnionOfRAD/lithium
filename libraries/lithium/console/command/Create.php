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
		$this->template = $this->template ?: $this->request->args(0);
		$defaults = array('prefix' => null, 'path' => null);
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
			$this->request->params['i'] = $this->i;
			$this->request->params['template'] = $this->template;

			$this->_commands[$command] = new $class(array(
				'request' => $this->request->shift(2),
				'classes'=> $this->_classes,
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
	 * @return boolean
	 */
	public function run($command = null) {
		if (!$command) {
			$command = $this->in('What would you like to create?', array(
				'choices' => array('model', 'view', 'controller', 'test', 'mock')
			));
		}
		if (!$command) {
			return false;
		}
		$data = array();

		$params = $this->{$command}('params');

		foreach ($params as $i => $param) {
			if (!$data[$param] = $this->{$command}($param)) {
				$data[$param] = $this->request->args($i);
			}
		}
		if ($this->_save($data)) {
			return true;
		}
		$this->error("{$command} could not be created.");
		return false;
	}

	/**
	 * [-i] Ask questions and use answers to create.
	 *
	 * @return boolean
	 */
	public function interactive() {
		$this->i = true;
		return $this->run();
	}

	/**
	 * Parse a template to find available variables specified in `{:name}` format. Each variable
	 * corresponds to a method in the sub command. For example, a `{:namespace}` variable will
	 * call the namespace method in the model command when `li3 create model Post` is called.
	 *
	 * @return array
	 */
	protected function _params() {
		$contents = $this->_template();

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
	 * @return string
	 */
	protected function _template() {
		$file = Libraries::locate('command.create.template', $this->template, array(
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
	 * @param array $options
	 * @return string
	 */
	protected function _namespace($name = null, $options  = array()) {
		$name = $name ?: $this->request->command;
		$defaults = array(
			'prefix' => $this->_library['prefix'],
			'prepend' => null,
			'spaces' => array(
				'model' => 'models', 'view' => 'views', 'controller' => 'controllers',
				'command' => 'extensions.command', 'adapter' => 'extensions.adapter',
				'helper' => 'extensions.helper'
			)
		);
		$options += $defaults;

		if (isset($options['spaces'][$name])) {
			$name = $options['spaces'][$name];
		}
		return str_replace('.', '\\', $options['prefix'] . $options['prepend'] . $name);
	}

	/**
	 * Save a template with the current params. Writes file to `Create::$path`.
	 *
	 * @param string $params
	 * @return boolean
	 */
	protected function _save($params = array()) {
		$defaults = array('namespace' => null, 'class' => null);
		$params += $defaults;
		if (empty($params['class'])) {
			return false;
		}
		$contents = $this->_template();
		$result = String::insert($contents, $params);

		$path = $this->_library['path'] . str_replace(
			array('\\', $this->library), array('/',''),
			"{$params['namespace']}\\{$params['class']}"
		);
		$file = str_replace('//', '/', "{$path}.php");
		$directory = dirname($file);

		if (!is_dir($directory)) {
			if (!mkdir($directory, 0755, true)) {
				return false;
			}
		}
		$this->out("{$params['class']} created in {$params['namespace']}.");
		return file_put_contents($file, "<?php\n\n{$result}\n\n?>");
	}
}

?>