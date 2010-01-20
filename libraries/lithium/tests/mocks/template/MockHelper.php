<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\template;

class MockHelper extends \lithium\template\Helper {

	/**
	 * Hack to expose protected properties for testing.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		return isset($this->{$property}) ? $this->{$property} : null;
	}
}

?>