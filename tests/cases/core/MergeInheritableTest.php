<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
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

