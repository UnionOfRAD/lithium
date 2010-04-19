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

	protected function _class() {
		return $this->request->action;
	}
}

?>