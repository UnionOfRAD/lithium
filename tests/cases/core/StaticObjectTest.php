<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\core;

use lithium\aop\Filters;
use lithium\tests\mocks\core\MockRequest;
use lithium\tests\mocks\core\MockStaticInstantiator;
use lithium\tests\mocks\core\MockStaticObject;

/**
 * @deprecated
 */
class StaticObjectTest extends \lithium\test\Unit {

	protected $_backup = null;

	public function setUp() {
		error_reporting(($this->_backup = error_reporting()) & ~E_USER_DEPRECATED);
	}

	public function tearDown() {
		error_reporting($this->_backup);
	}

	public function testInstanceWithClassesKey() {
		$expected = 'lithium\tests\mocks\core\MockRequest';
		$result = get_class(MockStaticInstantiator::instance('request'));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceWithNamespacedClass() {
		$expected = 'lithium\tests\mocks\core\MockRequest';
		$result = get_class(MockStaticInstantiator::instance(
			'lithium\tests\mocks\core\MockRequest'
		));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceWithObject() {
		$request = new MockRequest();
		$expected = 'lithium\tests\mocks\core\MockRequest';
		$result = get_class(MockStaticInstantiator::instance($request));
		$this->assertEqual($expected, $result);
	}

	public function testInstanceFalse() {
		$this->assertException('/^Invalid class lookup/', function() {
			MockStaticInstantiator::instance(false);
		});
	}
}

?>