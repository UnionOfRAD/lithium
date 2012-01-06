<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data\Collection;

class MockRecordSet extends \Lithium\Data\Collection\RecordSet {

	public function get($var) {
		return $this->{$var};
	}

	public function set($var, $value) {
		$this->{$var} = $value;
	}
}

?>