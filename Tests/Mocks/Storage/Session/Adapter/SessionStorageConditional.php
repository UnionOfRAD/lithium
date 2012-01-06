<?php

namespace Lithium\Tests\Mocks\Storage\Session\Adapter;

class SessionStorageConditional extends \Lithium\Storage\Session\Adapter\Memory {

	public function read($key = null, array $options = array()) {
		return isset($options['fail']) ? null : parent::read($key, $options);
	}

	public function write($key, $value, array $options = array()) {
		return isset($options['fail']) ? null : parent::write($key, $value, $options);
	}
}

?>