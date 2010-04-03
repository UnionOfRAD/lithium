<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\create;

use \lithium\util\Inflector;

class ControllerTwo extends \lithium\console\command\Create {

	public function __call($name, $options = array()) {
		return call_user_func_array("_{$name}", $options);
	}

	protected function _namespace($options = array()) {

	}

	protected function _use($options = array()) {

	}

	protected function _class($options = array()) {

	}

	protected function _plural($options = array()) {

	}

	protected function _model($options = array()) {

	}

	protected function _singular($options = array()) {

	}
}

?>