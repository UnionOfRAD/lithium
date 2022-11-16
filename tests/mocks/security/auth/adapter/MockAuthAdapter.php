<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\security\auth\adapter;

class MockAuthAdapter {

	public function check($credentials, array $options = []) {
		switch (true) {
			case isset($options['success']):
				return $credentials;
			case isset($options['keyOnly']):
				return $credentials['id'];
		}
		return false;
	}

	public function set($data, array $options = []) {
		if (isset($options['fail'])) {
			return false;
		}
		return $data;
	}

	public function clear(array $options = []) {
	}
}

?>