<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\session\strategy;

/**
 * A `MissingSignatureException` may be thrown when reading data from a session-based storage that
 * is expecting an HMAC signature, but none is found..
 */
class MissingSignatureException extends \RuntimeException {

	protected $code = 403;
}

?>