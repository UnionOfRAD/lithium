<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\security\validation;

class MockFormSignature extends \lithium\security\validation\FormSignature {

	public static $compile = [];

	public static $parse = [];

	protected static function _compile(array $fields, array $locked, array $excluded) {
		$result = parent::_compile($fields, $locked, $excluded);

		static::$compile[] = [
			'in' => compact('fields', 'locked', 'excluded'),
			'out' => $result
		];
		return $result;
	}

	protected static function _parse($signature) {
		$result = parent::_parse($signature);

		static::$parse[] = [
			'in' => compact('signature'),
			'out' => $result
		];
		return $result;
	}

	public static function reset() {
		static::$compile = [];
		static::$parse = [];
	}
}

?>