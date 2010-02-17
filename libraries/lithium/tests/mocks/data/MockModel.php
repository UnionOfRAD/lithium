<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockModel extends \lithium\data\Model {

	public static function key($values = array('id' => null)) {
		if (is_object($values)) {
			$values = $values->to('array');
		}
		return $values['id'];
	}

	public static function __init(array $options = array()) {}
}

?>