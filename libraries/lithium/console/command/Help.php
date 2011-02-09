<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command;

use lithium\core\Libraries;
use lithium\util\Inflector;
use lithium\analysis\Inspector;
use lithium\analysis\Docblock;

/**
 * Get information about a particular class including methods, properties,
 * and descriptions.
 */
class Help extends \lithium\console\Command {

	/**
	 * Auto run the help command.
	 *
	 * @param string $command Name of the command to return help about.
	 * @param string $method The method of the command to get help for.
	 *        This defaults to `'run'` which is the default main method
	 *        for lithium commands.
	 * @return void
	 */
	public function run($command = null, $method = 'run') {
		if (!$command) {
			$this->_renderCommands();
			return true;
		}
		$command = Inflector::classify($command);

		if (!$class = Libraries::locate('command', $command)) {
			$this->error("{$command} not found");
			return false;
		}
		if (strpos($command, '\\') !== false) {
			$command = join('', array_slice(explode("\\", $command), -1));
		}
		$command = strtolower(Inflector::slug($command));

		$methods = $this->_methods($class);
		$properties = $this->_properties($class);
		$info = Inspector::info($class);

		$this->_renderUsage($command, $method, $methods, $properties);

		if (!empty($info['description'])) {
			$this->nl();
			$this->_renderDescription($info);
			$this->nl();
		}

		if ($properties || $methods) {
			$this->out('OPTIONS', 'heading');
		}
		if ($properties) {
			$this->_render($properties);
		}
		if ($methods) {
			$this->_render($methods);
		}
		return true;
	}

	/**
	 * Get the api for the class.
	 *
	 * @param string $class fully namespaced class in dot notation.
	 * @param string $type method|property
	 * @param string $name the name of the method or property.
	 * @return array
	 */
	public function api($class = null, $type = null, $name = null) {
		$class = str_replace(".", "\\", $class);

		switch ($type) {
			default:
				$info = Inspector::info($class);
				$result = array('class' => array(
					'name' => Inflector::classify($info['shortName']),
					'description' => $info['description']
				));
			break;
			case 'method':
				$result = $this->_methods($class, compact('name'));
			break;
			case 'property':
				$result = $this->_properties($class, compact('name'));
			break;
		}
		$this->_render($result);
	}

	/**
	 * Get the methods for the class.
	 *
	 * @param string $class
	 * @param array $options
	 * @return array
	 */
	protected function _methods($class, $options = array()) {
		$defaults = array('name' => null);
		$options += $defaults;

		$map = function($item) {
			if ($item->name[0] === '_') {
				return;
			}
			$modifiers = array_values(Inspector::invokeMethod('_modifiers', array($item)));
			$setAccess = array_intersect($modifiers, array('private', 'protected')) != array();

			if ($setAccess) {
				$item->setAccessible(true);
			}
			$result = compact('modifiers') + array(
				'docComment' => $item->getDocComment(),
				'name' => $item->getName()
			);
			if ($setAccess) {
				$item->setAccessible(false);
			}
			return $result;
		};

		$methods = Inspector::methods($class)->map($map, array('collect' => false));
		$results = array();

		foreach ($methods as $method) {
			$comment = Docblock::comment($method['docComment']);

			$name = $method['name'];
			$description = $comment['description'];

			$args = $return = null;

			if (isset($comment['tags']['params'])) {
				$args = $comment['tags']['params'];
			}
			if (isset($comment['tags']['return'])) {
				$return = trim(strtok($comment['tags']['return'], ' '));
			}

			$usage = null;
			if (!empty($method['args'])) {
				foreach ((array) $method['args'] as $arg => $description) {
					$usage .= ' ' . trim($arg, ' $');
				}
			}
			$results[$name] = compact('name', 'description', 'return', 'args', 'usage');

			if ($name && $name == $options['name']) {
				return array($name => $results[$name]);
			}
		}
		return $results;
	}

	/**
	 * Get the properties for the class.
	 *
	 * @param string $class
	 * @param array $options
	 * @return array
	 */
	protected function _properties($class, $options = array()) {
		$defaults = array('name' => null);
		$options += $defaults;

		$properties = Inspector::properties($class);
		$results = array();

		foreach ($properties as &$property) {
			$name = str_replace('_', '-', Inflector::underscore($property['name']));

			$comment = Docblock::comment($property['docComment']);
			$description = trim($comment['description']);
			$type = isset($comment['tags']['var']) ? strtok($comment['tags']['var'], ' ') : null;

			$usage = strlen($name) == 1 ? "-{$name}" : "--{$name}";
			if ($type != 'boolean') {
				$usage .= "=<{$type}>";
			}

			$results[$name] = compact('name', 'description', 'type', 'usage');

			if ($name == $options['name']) {
				return array($name => $results[$name]);
			}
		}
		return $results;
	}

	/**
	 * Output the formatted properties or methods.
	 *
	 * @see lithium\console\command\Help::_properties()
	 * @see lithium\console\command\Help::_methods()
	 * @param array $params From `_properties()` or `_methods()`.
	 * @return void
	 */
	protected function _render($params) {
		foreach ($params as $name => $param) {
			if ($name === 'run' || empty($param['name'])) {
				continue;
			}
			$usage = (!empty($param['usage'])) ? trim($param['usage']) : $param['name'];
			$this->out($this->_pad($usage), 'option');

			if (!empty($param['args'])) {
				$args = array();
				foreach ((array) $param['args'] as $arg => $desc) {
					$arg = str_replace('$', '', trim($arg));
					$args[] = $this->_pad("{$arg}: {$desc['text']}", 2);
				}
				$this->out($args);
			}
			if ($param['description']) {
				$this->out($this->_pad($param['description'], 2));
			}
			$this->nl();
		}
	}

	/**
	 * Output the formatted available commands.
	 *
	 * @return void
	 */
	protected function _renderCommands() {
		$commands = Libraries::locate('command', null, array('recursive' => false));

		foreach ($commands as $key => $command) {
			$library = strtok($command, '\\');

			if (!$key || strtok($commands[$key - 1] , '\\') != $library) {
				$this->out("{:heading}COMMANDS{:end} {:blue}via {$library}{:end}");
			}
			$info = Inspector::info($command);
			$name = strtolower(Inflector::slug($info['shortName']));

			$this->out($this->_pad($name) , 'heading');
			$this->out($this->_pad($info['description']), 2);
		}

		$message  = 'See `{:command}li3 help COMMAND{:end}`';
		$message .= ' for more information on a specific command.';
		$this->out($message, 2);
	}

	/**
	 * Output the formatted usage.
	 *
	 * @see lithium\console\command\Help::_methods()
	 * @see lithium\console\command\Help::_properties()
	 * @param string $name The name of the command.
	 * @param string $method The method of the command.
	 * @param string $methods An array of method with associated information.
	 * @param array $properties From `_properties()`.
	 * @return void
	 */
	protected function _renderUsage($name, $method, $methods, $properties) {
		$this->out('USAGE', 'heading');

		$template = "{:command}li3 %s{:end}{:command}%s{:end}{:option}%s{:end}";
		$method = $methods[$method];

		$params = array_reduce($properties, function($a, $b) {
			return "{$a} [{$b['usage']}]";
		});
		$args = $method['usage'];

		$this->out($this->_pad(sprintf($template,
			$name ?: 'COMMAND',
			$params,
			$args
		)));
	}

	/**
	 * Output the formatted command description.
	 *
	 * @param array $info Info from insepcting the class of the command.
	 * @return void
	 */
	protected function _renderDescription($info) {
		$this->out('DESCRIPTION', 'heading');
		$this->out($this->_pad(strtok($info['description'], "\n"), 1));
	}

	/**
	 * Add left padding for prettier display.
	 *
	 * @param string $message the text to render.
	 * @param string $level the level of indentation.
	 * @return void
	 */
	protected function _pad($message, $level = 1) {
		$padding = str_repeat(' ', $level * 4);
		return $padding . str_replace("\n", "\n{$padding}", $message);
	}
}

?>