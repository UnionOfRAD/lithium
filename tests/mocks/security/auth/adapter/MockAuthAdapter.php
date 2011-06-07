<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\security\auth\adapter;

class MockAuthAdapter extends \lithium\core\Object {

	public function check($credentials, array $options = array()) {
		return isset($options['success']) ? $credentials : false;
	}

	public function set($data, array $options = array()) {
		if (isset($options['fail'])) {
			return false;
		}
		return $data;
	}

	public function clear(array $options = array()) {
	}
}

?>