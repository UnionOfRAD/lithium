<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\model;

/**
 * The `MediaException` is thrown when a request is made to render content in a format not
 * supported.
 *
 * @see lithium\net\http\Media
 */
class QueryException extends \RuntimeException {

	protected $code = 500;
}

?>