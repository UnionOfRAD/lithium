<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Core;

/**
 * A `ClassNotFoundException` may be thrown when a configured adapter or other service class defined
 * in configuration can't be located.
 */
class ClassNotFoundException extends \RuntimeException {

	protected $code = 500;
}

?>