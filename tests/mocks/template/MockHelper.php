<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\template;

class MockHelper extends \lithium\template\Helper {

	protected $_strings = ['link' => '<a href="{:url}"{:options}>{:title}</a>'];

	/**
	 * Hack to expose protected properties for testing.
	 *
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		return isset($this->{$property}) ? $this->{$property} : null;
	}

	public function testOptions($defaults, $options) {
		return $this->_options($defaults, $options);
	}

	public function testRender($method, $string, $params, array $options = []) {
		return $this->_render($method, $string, $params, $options);
	}
}

?>