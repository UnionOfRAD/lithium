<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\core;

/**
 * A `ClassNotFoundException` may be thrown when a configured adapter or other service class defined
 * in configuration can't be located.
 */
class ClassNotFoundException extends \RuntimeException {

	protected $code = 500;
}

?>