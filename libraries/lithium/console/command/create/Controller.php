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

	protected function _use() {
		return '\\' . $this->_namespace('model') . '\\' . $this->_model();
	}

	protected function _class() {
		return $this->request->action . 'Controller';
	}

	protected function _plural() {
		return Inflector::pluralize(Inflector::camelize($this->request->action, false));
	}

	protected function _model() {
		return Inflector::classify($this->request->action);
	}

	protected function _singular() {
		return Inflector::singularize(Inflector::camelize($this->request->action, false));
	}
}

?>