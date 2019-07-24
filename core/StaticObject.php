<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\core;

$message  = "lithium\core\StaticObject has been deprecated.";
$message .= "The old class and methods continue to work and redirect calls. ";
$message .= "It is possible to use this class as `StaticObjectDeprecated` with PHP >=7.2.";
trigger_error($message, E_USER_DEPRECATED);

if (PHP_VERSION_ID < 70200) {
	class_alias('StaticObjectDeprecated', 'StaticObject');
}

?>