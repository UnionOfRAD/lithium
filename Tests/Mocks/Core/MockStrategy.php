<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Core;

class MockStrategy extends \Lithium\Core\Adaptable {

	protected static $_configurations = array();

	protected static $_strategies = 'Strategy.Storage.Cache';
}

?>