<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\build;

use \lithium\core\Libraries;
use \lithium\util\Inflector;

class Controller extends \lithium\console\command\Build {

	public function run($name = null, $null = null) {
		$library = Libraries::get($this->library);
		if (empty($library['prefix'])) {
			return false;
		}
		$model = Inflector::classify($name);
		$use = "\\{$library['prefix']}\\models\\{$model}";
	}
}

?>