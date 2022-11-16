<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\net\http;

class Template {

	public function __construct(array $config = []) {
		$config['response']->headers('Custom', 'Value');
	}

	public function render() {}
}

?>