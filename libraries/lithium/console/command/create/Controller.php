<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\create;

use \lithium\core\Libraries;
use \lithium\util\Inflector;

/**
 * Creates a Lithium controller in the \app\controllers namespace.
 *
 */
class Controller extends \lithium\console\command\Create {

	/**
	 * Generate a new controller by name.
	 *
	 * @param string $name Controller name.
	 * @param string $null 
	 * @return void
	 */
	public function run($name = null, $null = null) {
		$library = Libraries::get($this->library);
		if (empty($library['prefix'])) {
			return false;
		}
		$model = Inflector::classify($name);
		$use = "\\{$library['prefix']}models\\{$model}";

		$params = array(
			'namespace' => "{$library['prefix']}controllers",
			'use' => $use,
			'class' => "{$name}Controller",
			'model' => $model,
			'singular' => Inflector::singularize(Inflector::underscore($name)),
			'plural' => Inflector::pluralize(Inflector::underscore($name))
		);

		if ($this->_save($this->template, $params)) {
			$this->out(
				"{$params['class']} created in {$params['namespace']}."
			);
			return true;
		}
		return false;
	}
}

?>