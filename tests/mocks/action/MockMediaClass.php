<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\action;

class MockMediaClass extends \lithium\net\http\Media {

	public static function render($response, $data = null, array $options = []) {
		$response->options = $options;
		$response->data = $data;
		return $response;
	}
}

?>