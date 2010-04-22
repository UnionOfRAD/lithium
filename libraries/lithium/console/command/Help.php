<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command;

use \lithium\core\Libraries;
use \lithium\util\Inflector;
use \lithium\analysis\Inspector;
use \lithium\analysis\Docblock;

/**
 * Get information about a particular class including methods, properties, and descriptions.
 *
 */
class Help extends \lithium\console\Command {

	/**
	 * Auto run the help command
	 *
	 * @param string $name COMMAND to get help
	 * @return void
	 */
	public function run($name = null) {
		if (!$name) {
			$this->out('COMMANDS', 'heading1', 2);
			$commands = Libraries::locate('command', null, array('recursive' => false));

			foreach ($commands as $command) {
				$info = Inspector::info($command);
				$this->out($this->_pad(Inflector::classify($info['shortName'])), 'heading2');
				$this->out($this->_pad($info['description']), 2);
			}
			$message = 'See `{:command}li3 help COMMAND{:end}`';
			$message .= ' for more information on a specific command.';
			$this->out($message, 2);
			return true;
		}
		if (!$class = Libraries::locate('command', $name)) {
			$this->error("{$name} not found");
			return false;
		}
		if (strpos($name, '\\') !== false) {
			$name = join('', array_slice(explode("\\", $name), -1));
		}
		$methods = $this->_methods($class);
		$properties = $this->_properties($class);

		$this->out('USAGE', 'heading1');
		$this->out($this->_pad(sprintf("{:command}li3 %s{:end}{:option}%s{:end} [ARGS]",
			$name ?: 'COMMAND',
			array_reduce($properties, function($a, $b) {
				return "{$a} {$b['usage']}";
			})
		)));
		$info = Inspector::info($class);

		if (!empty($info['description'])) {
			$this->nl();
			$this->out('DESCRIPTION');
			$this->out($this->_pad(strtok($info['description'], "\n"), 1));
			$this->nl();
		}

		if ($properties || $methods) {
			$this->out('OPTIONS', 'heading2');
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
	 * @param string $class fully namespaced class in dot notation
	 * @param string $type method|property
	 * @param string $name the name of the method or property
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
	 * Get the methods for the class
	 *
	 * @param string $class
	 * @param array $options
	 * @return array
	 */
	protected function _methods($class, $options = array()) {
		$defaults = array('name' => null);
		$options += $defaults;
		$methods = Inspector::methods($class)->map(
			function($item) {
				if ($item->name[0] === '_') {
					return;
				}

				$modifiers = array_values(Inspector::invokeMethod('_modifiers', array($item)));
				$setAccess = (
					array_intersect($modifiers, array('private', 'protected')) != array()
				);
				if ($setAccess) {
					$item->setAccessible(true);
				}
				$result = compact('modifiers') + array(
					'docComment' => $item->getDocComment(),
					'name' => $item->getName(),
				);
				if ($setAccess) {
					$item->setAccessible(false);
				}
				return $result;
			},
			array('collect' => false)
		);
		$results = array();
		foreach ($methods as $method) {
			$comment = Docblock::comment($method['docComment']);
			$name = $method['name'];
			$description = $comment['description'];
			$args = !isset($comment['tags']['params']) ? null : $comment['tags']['params'];
			$return = !isset($comment['tags']['return']) ? null :
				trim(strtok($comment['tags']['return'], ' '));
			$command = $name === 'run' ? null : $name;
			$command = !$command && !empty($args) ? '[ARGS]' : $command;
			$usage = "{$command} ";
			$usage .= empty($args) ? null : join(' ', array_map(function ($a) {
					return '[' . str_replace('$', '', trim($a)) . ']';
			}, array_keys($args)));

			$results[$name] = compact('name', 'description', 'return', 'args', 'usage');

			if ($name && $name == $options['name']) {
				return array($name => $results[$name]);
			}
		}
		return $results;
	}

	/**
	 * Get the properties for the class
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
			$comment = Docblock::comment($property['docComment']);
			$description = trim($comment['description']);
			$type = isset($comment['tags']['var']) ? strtok($comment['tags']['var'], ' ') : null;
			$name = str_replace('_', '-', Inflector::underscore($property['name']));
			$usage = $type == 'boolean' ? "-{$name}" : "--{$name}=" . strtoupper($name);
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
	 * @param array $params from _properties|_methods
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
	 * Add left padding for prettier display.
	 *
	 * @param string $message the text to render
	 * @param string $level the level of indentation
	 * @return void
	 */
	protected function _pad($message, $level = 1) {
		$padding = str_repeat(' ', $level * 4);
		return $padding . str_replace("\n", "\n{$padding}", $message);
	}
}

?>