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
use lithium\tests\mocks\core\MockMethodFiltering;
use lithium\tests\mocks\core\MockExposed;
use lithium\tests\mocks\core\MockCallable;
use lithium\tests\mocks\core\MockObjectForParents;
use lithium\tests\mocks\core\MockObjectConfiguration;

class ObjectTest extends \lithium\test\Unit {

	protected $_backup = null;

	public function setUp() {
		error_reporting(($this->_backup = error_reporting()) & ~E_USER_DEPRECATED);
	}

	public function tearDown() {
		error_reporting($this->_backup);
	}

	/**
	 * Test configuration handling
	 */
	public function testObjectConfiguration() {
		$expected = ['testScalar' => 'default', 'testArray' => ['default']];
		$config = new MockObjectConfiguration();
		$this->assertEqual($expected, $config->getConfig());

		$config = new MockObjectConfiguration(['autoConfig' => ['testInvalid']]);
		$this->assertEqual($expected, $config->getConfig());

		$expected = ['testScalar' => 'override', 'testArray' => ['default', 'override']];
		$config = new MockObjectConfiguration(['autoConfig' => [
			'testScalar', 'testArray' => 'merge'
		]] + $expected);
		$this->assertEqual($expected, $config->getConfig());
	}
}

?>