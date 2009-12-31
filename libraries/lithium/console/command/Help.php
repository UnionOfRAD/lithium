<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\build;

/**
 * Get information about a particular class like methods, properties, descriptions
 *
 */
class Help extends \lithium\console\Command {
	
	/**
	 * undocumented function
	 *
	 * @return void
	 */
	public function run($class) {
		
		$class = get_class($this);

		$pad = function($message, $level = 1) {
			$padding = str_repeat(' ', $level * 4);
			return $padding . str_replace("\n", "\n{$padding}", $message);
		};

		$this->out('USAGE');
		$this->out($pad(sprintf("li3 %s%s [ARGS]",
			$this->request->params['command'] ?: 'COMMAND',
			array_reduce($properties, function($a, $b) { return "{$a} {$b['usage']}"; })
		)));

		if ($this->request->command) {
			$this->nl();
			$this->out('DESCRIPTION');
			$info =Inspector::info($class);
			$this->out($pad($info['description']));
		}
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
		if ($methods) {
			$this->nl();
			$this->out('tasks');

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
		if (!$this->request->command) {
			$this->nl();
			$this->out('COMMANDS');
			$commands = Libraries::locate('command', null, array('recursive' => false));

			foreach ($commands as $command) {
				$info = Inspector::info($command);
				$this->out($pad(Inflector::underscore($info['shortName'])));
				$this->out($pad($info['description'], 2));
				$this->nl();
			}
			$this->out('See `li3 COMMAND help` for more information on a specific command.');
		}
		return true;
	}
		
	public function class($type, $name = null) {
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
	
	protected function _methods($class) {
		$methods = Inspector::methods($class)->map(
			function($item) {
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
			$args = isset($comment['tags']['params']) ? join(' ', array_keys($comment['tags']['params'])) : null;
			$return = isset($comment['tags']['return']) ? strtok($comment['tags']['return'], ' ') : null;
			$usage = trim("{$command} {$args}");

			$method = compact('name', 'description', 'return', 'usage');
		}
		return $methods;
	}
	
	protected function _properties($class) {
		$properties = Inspector::properties(get_class($this));

		foreach ($properties as &$property) {
			$comment = Docblock::comment($property['docComment']);
			$description = $comment['description'];
			$type = isset($comment['tags']['var']) ? strtok($comment['tags']['var'], ' ') : null;

			$name = str_replace('_', '-', Inflector::underscore($property['name']));
			$usage = $type == 'boolean' ? "-{$name}" : "--{$name}=" . strtoupper($name);

			$property = compact('name', 'description', 'type', 'usage');
		}
	}
	
}

?>