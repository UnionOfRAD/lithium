<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Data\Model;

/**
 * The `QueryException` is thrown when a CRUD operation on the database returns an
 * error.
 *
 * @see Lithium\Data\Model\Query
 */
class QueryException extends \RuntimeException {

	protected $code = 500;
}

?>