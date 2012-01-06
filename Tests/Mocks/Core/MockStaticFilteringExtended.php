<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Core;

class MockStaticFilteringExtended extends \Lithium\Tests\Mocks\Core\MockStaticMethodFiltering {

	public static function childMethod() {
		return 'Working';
	}
}

?>