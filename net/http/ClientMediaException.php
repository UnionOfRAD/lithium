<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\net\http;

/**
 * The `ClientMediaException` is thrown when a request body could not be understood or decoded by
 * the server.
 *
 * @see lithium\net\http\Media
 */
class ClientMediaException extends \lithium\net\http\MediaException {

	protected $code = 415;
}

?>