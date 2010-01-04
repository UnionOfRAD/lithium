<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command;

use \lithium\core\Libraries;
use \lithium\util\Inflector;
use \lithium\util\reflection\Inspector;
use \lithium\util\reflection\Docblock;

/**
 * Get information about a particular class like methods, properties, descriptions
 *
 */
class Help extends \lithium\console\Command {

	/**
	 * Auto run the help command
	 *
	 * @param string $name
	 * @return void
	 */
	public function run($name = null) {
		$class = Libraries::locate('command', $name);
		if (!$class) {
			$this->error("{$name} not found");
		}
		$pad = function($message, $level = 1) {
			$padding = str_repeat(' ', $level * 4);
			return $padding . str_replace("\n", "\n{$padding}", $message);
		};

		if (class_exists($class)) {
			$properties = $this->_properties($class);
			$this->out('USAGE');
			$this->out($pad(sprintf("li3 %s%s [ARGS]",
				$name ?: 'COMMAND',
				array_reduce($properties, function($a, $b) { return "{$a} {$b['usage']}"; })
			)));

			$this->nl();
			$this->out('DESCRIPTION');
			$info = Inspector::info($class);
			$this->out($pad($info['description']));

			if ($properties) {
				$this->nl();
				$this->out('OPTIONS');

				foreach ($properties as $param) {
					$this->out($pad($param['usage']));

					if ($param['description']) {
						$this->out($pad($param['description'], 2));
					}
					$this->nl();
				}
			}
			if ($methods = $this->_methods($class)) {
				$this->nl();
				$this->out('TASKS');

				foreach ($methods as $param) {
					if (empty($param['usage'])) {
						continue;
					}
					$this->out($pad($param['usage']));

					if ($param['description']) {
						$this->out($pad($param['description'], 2));
					}
					$this->nl();
				}
			}
			return true;
		}

		$this->nl();
		$this->out('COMMANDS');
		$commands = Libraries::locate('command', null, array('recursive' => false));

		foreach ($commands as $command) {
			$info = Inspector::info($command);
			$this->out($pad(Inflector::underscore($info['shortName'])));
			$this->out($pad($info['description'], 2));
			$this->nl();
		}
		$this->out('See `li3 help COMMAND` for more information on a specific command.');
		return true;
	}

	/**
	 * Get the api for the class
	 *
	 * @param string $type (method, properties)
	 * @param string $name
	 * @return array
	 */
	public function api($type, $name = null) {
		if ($name === null) {
			$name = $type;
			$type = null;
		}
		switch ($type) {
			default:
				$info = Inspector::info($name);
				$this->out($pad(Inflector::underscore($info['shortName'])));
				$this->out($pad($info['description'], 2));
			break;
			case 'method':

			break;
			case 'property':

			break;
		}
	}

	/**
	 * Get the methods for the class
	 *
	 * @param string $class
	 * @return array
	 */
	protected function _methods($class) {
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
		foreach ($methods as &$method) {
			$comment = Docblock::comment($method['docComment']);
			$name = $method['name'];
			$command = $method['name'] === 'run' ? null : $method['name'];
			$description = $method['name'] === 'run' ? null : $comment['description'];
			$args = isset($comment['tags']['params'])
				? join(' ', array_keys($comment['tags']['params'])) : null;
			$return = isset($comment['tags']['return'])
				? strtok($comment['tags']['return'], ' ') : null;
			$usage = trim("{$command} {$args}");
			$method = compact('name', 'description', 'return', 'usage');
		}
		return $methods;
	}

	/**
	 * Get the properties for the class
	 *
	 * @param string $class
	 * @return array
	 */
	protected function _properties($class) {
		$properties = Inspector::properties($class);

		foreach ($properties as &$property) {
			$comment = Docblock::comment($property['docComment']);
			$description = $comment['description'];
			$type = isset($comment['tags']['var']) ? strtok($comment['tags']['var'], ' ') : null;

			$name = str_replace('_', '-', Inflector::underscore($property['name']));
			$usage = $type == 'boolean' ? "-{$name}" : "--{$name}=" . strtoupper($name);

			$property = compact('name', 'description', 'type', 'usage');
		}

		return $properties;
	}
}

?>