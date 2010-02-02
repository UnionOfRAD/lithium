<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

class MockRecordSet extends \lithium\data\model\RecordSet {

	public function get($var) {
		return $this->{$var};
	}

	public function set($var, $value) {
		$this->{$var} = $value;
	}
}

?>