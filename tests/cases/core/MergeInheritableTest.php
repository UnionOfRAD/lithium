<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\core;

class A {
	public $foo = [
		'a' => true
	];
}
class B extends A {
	public $foo = [
		'b' => true
	];
}
class C extends B {
	use \lithium\core\MergeInheritable;

	public $foo = [
		'c' => true
	];

	public function __construct() {
		$this->_inherit(['foo']);
	}
}
class D extends C {
	public $foo = [
		'd' => true
	];
}

class MergeInheritableTest extends \lithium\test\Unit {

	public function testStopsWhereDefined() {
		$subject = new C();

		$expected = [
			'c' => true
		];
		$result = $subject->foo;
		$this->assertEqual($expected, $result);
	}

	public function testWithFullParentsChain() {
		$subject = new D();

		$expected = [
			'd' => true,
			'c' => true
		];
		$result = $subject->foo;
		$this->assertEqual($expected, $result);
	}
}

