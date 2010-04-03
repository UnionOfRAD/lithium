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
	 */
	public $library = 'app';

	/**
	 * The name of the template to use to generate the file. This allows you to add a custom
	 * template to be used in place of the core template for each command. Place templates in
	 * `<library>\extensions\command\create\template`.
	 *
	 */
	public $template = null;

	/**
	 * The variable parameters of the template.
	 *
	 * @var array
	 */
	protected $_params = array();

	/**
	 * Class Constrcutor.
	 *
	 * @param string $config
	 */
	public function __construct($config = array()) {
		$this->template = strtolower(join('', array_slice(explode("\\", get_class($this)), -1)));
		parent::__construct($config);
	}

	/**
	 * Class initializer. Parses template and sets up params that need to be filled.
	 *
	 * @return void
	 */
	public function _init() {
		parent::_init();
		$this->_params = $this->_parse($this->template);
	}

	/**
	 * Run the create command. Takes `$command` and delegates to `$command::$method`
	 *
	 * @param string $command
	 * @param string $method
	 * @return void
	 */
	public function run($command = null, $method = 'run') {
		if (!$command || $this->$i) {
			return $this->interactive();
		}
		$class = Libraries::locate('command.create', $command);
		$command = new $class(array(
			'request' => $this->request->shift(2), 'classes'=> $this->_classes
		));

		if (!method_exists($command, $method)) {
			array_unshift($command->request->params['args'], $method);
			$method = 'run';
		}
		return $command->invokeMethod($method, $command->request->params['args']);
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
	protected function _parse($template) {
		$contents = $this->_template($template);
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
	protected function _template($name) {
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
	protected function _namespace($name) {
		$nameToSpace = array(
			'model' => 'models', 'view' => 'views', 'controller' => 'controllers',
			'command' => 'extensions.command', 'adapter' => 'extensions.adapter',
			'helper' => 'extensions.helper'
		);
		if (isset($nameToSpace[$name])) {
			$name = $nameToSpace[$name];
		}
		return str_replace('.', '\\', $name);
	}

	/**
	 * Save a template with the current params. Writes file to `Create::$path`.
	 *
	 * @param string $template
	 * @param string $params
	 * @return boolean
	 */
	protected function _save($template, $params = array()) {
		$contents = $this->_tempalte($template);
		$result = String::insert($contents, $params);
		$library = Libraries::get($this->library);

		if (!empty($library['path'])) {
			$path = $library['path'] . str_replace(array('\\', $this->library), array('/',''),
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