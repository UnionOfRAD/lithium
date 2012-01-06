<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Util;

class MockCollectionMarker {

	public $marker = false;

	public $data = 'foo';

	public function mark() {
		$this->marker = true;
		return true;
	}

	function mapArray() {
		return array('foo');
	}
}

?>