<?php

namespace lithium\tests\mocks\storage\session\adapter;

use \lithium\storage\session\adapter\Memory;

class SessionStorageConditional extends Memory {

	public function read($key, $options = array()) {
		return isset($options['fail']) ? null : parent::read($key, $options);
	}

	public function write($key, $value, $options = array()) {
		return isset($options['fail']) ? null : parent::write($key, $value, $options);
	}
}

?>