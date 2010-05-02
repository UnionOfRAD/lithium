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

	protected function _use($request) {
		$request->params['command'] = 'model';
		return '\\' . $this->_namespace($request) . '\\' . $this->_model($request);
	}

	protected function _class($request) {
		return Inflector::camelize(Inflector::pluralize($request->action) . 'Controller');
	}

	protected function _plural($request) {
		return Inflector::pluralize(Inflector::camelize($request->action, false));
	}

	protected function _model($request) {
		return Inflector::classify($request->action);
	}

	protected function _singular($request) {
		return Inflector::singularize(Inflector::camelize($request->action, false));
	}
}

?>