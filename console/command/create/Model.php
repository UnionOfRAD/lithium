<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console\command\create;

use lithium\util\Inflector;

/**
 * Generate a Model class in the `--library` namespace
 *
 * `li3 create model Posts`
 * `li3 create --library=li3_plugin model Posts`
 *
 */
class Model extends \lithium\console\command\Create {

	/**
	 * Get the class name for the model.
	 *
	 * @param string $request
	 * @return string
	 */
	protected function _class($request) {
		return Inflector::camelize($request->action);
	}
}

?>