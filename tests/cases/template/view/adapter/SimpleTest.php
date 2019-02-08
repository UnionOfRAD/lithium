<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\template\view\adapter;

use lithium\template\view\adapter\Simple;
use lithium\tests\mocks\util\MockTextObject;

class SimpleTest extends \lithium\test\Unit {

	public $subject = null;

	public function setUp() {
		$this->subject = new Simple();
	}

	public function testBasicRender() {
		$result = $this->subject->template('layout', ['layout' => '{:content}']);
		$expected = '{:content}';
		$this->assertEqual($expected, $result);

		$message = new MockTextObject();
		$message->message = 'Lithium is about to rock you.';

		$result = $this->subject->render('Hello {:name}! {:message}', compact('message') + [
			'name' => 'World'
		]);
		$expected = 'Hello World! Lithium is about to rock you.';
		$this->assertEqual($expected, $result);
	}
}

?>