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
		$key = static::_object()->_meta['key'];

		if (method_exists($values, 'to')) {
			$values = $values->to('array');
		} elseif (isset($values->$key)) {
			return $values->$key;
		}
		return $values[$key];
	}

	public static function __init(array $options = array()) {}
}

?>