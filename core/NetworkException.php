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
 * A `NetworkException` may be thrown whenever an unsuccessful attempt is made to connect to a
 * remote service over the network. This may be a web service, a database, or another network
 * resource.
 */
class NetworkException extends \RuntimeException {

	protected $code = 503;
}

?>