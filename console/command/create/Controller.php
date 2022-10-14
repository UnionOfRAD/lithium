<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console\command\create;

use lithium\util\Inflector;

/**
 * Generate a Controller class in the `--library` namespace
 *
 * `li3 create controller Posts`
 * `li3 create --library=li3_plugin controller Posts`
 *
 */
class Controller extends \lithium\console\command\Create {

	public $path;

	/**
	 * Get the fully-qualified model class that is used by the controller.
	 *
	 * @param object $request
	 * @return string
	 */
	protected function _use($request) {
		$request->params['command'] = 'model';
		return $this->_namespace($request) . '\\' . $this->_model($request);
	}

	/**
	 * Get the controller class name.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _class($request) {
		return $this->_name($request) . 'Controller';
	}

	/**
	 * Returns the name of the controller class, minus `'Controller'`.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _name($request) {
		return Inflector::camelize($request->action);
	}

	/**
	 * Get the plural variable used for data in controller methods.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _plural($request) {
		return Inflector::pluralize(Inflector::camelize($request->action, false));
	}

	/**
	 * Get the model class used in controller methods.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _model($request) {
		return Inflector::camelize($request->action);
	}

	/**
	 * Get the singular variable to use for data in controller methods.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _singular($request) {
		return Inflector::singularize(Inflector::camelize($request->action, false));
	}
}

?>