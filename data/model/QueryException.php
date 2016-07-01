<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\model;

/**
 * The `QueryException` is thrown when a CRUD operation on the database returns an
 * error.
 *
 * @see lithium\data\model\Query
 */
class QueryException extends \RuntimeException {

	protected $code = 500;
}

?>