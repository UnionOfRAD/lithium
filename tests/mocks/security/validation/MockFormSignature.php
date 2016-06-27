<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\security\validation;

class MockFormSignature extends \lithium\security\validation\FormSignature {

	public static $compile = array();

	public static $parse = array();

	protected static function _compile(array $fields, array $locked, array $excluded) {
		$result = parent::_compile($fields, $locked, $excluded);

		static::$compile[] = array(
			'in' => compact('fields', 'locked', 'excluded'),
			'out' => $result
		);
		return $result;
	}

	protected static function _parse($signature) {
		$result = parent::_parse($signature);

		static::$parse[] = array(
			'in' => compact('signature'),
			'out' => $result
		);
		return $result;
	}

	public static function reset() {
		static::$compile = array();
		static::$parse = array();
	}
}

?>