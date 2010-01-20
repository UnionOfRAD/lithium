<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\security;

class Auth extends \lithium\core\Adaptable {

	/**
	 * Stores configurations for various authentication adapters.
	 *
	 * @var object `Collection` of authentication configurations.
	 */
	protected static $_configurations;
}

?>