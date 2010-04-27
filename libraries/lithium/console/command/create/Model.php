<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\create;

use \lithium\core\Libraries;

/**
 * Creates a new Lithium model in the \app\models namespace.
 *
 */
class Model extends \lithium\console\command\Create {

	/**
	 * Creates a new model by name.
	 *
	 * @param string $name Model name.
	 * @param string $null 
	 */
	public function run($name = null, $null = null) {
		$library = Libraries::get($this->library);
		if (empty($library['prefix'])) {
			return false;
		}
		$params = array(
			'namespace' => "{$library['prefix']}models",
			'class' => "{$name}",
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