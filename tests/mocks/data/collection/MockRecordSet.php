<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\collection;

class MockRecordSet extends \lithium\data\collection\RecordSet {

	public function get($var) {
		return $this->{$var};
	}

	public function set($var, $value) {
		$this->{$var} = $value;
	}
}

?>