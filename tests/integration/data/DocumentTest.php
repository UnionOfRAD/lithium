<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\data;

use lithium\tests\fixture\model\gallery\Galleries;

class DocumentTest extends \lithium\tests\integration\data\Base {

	/**
	 * Skip the test if no allowed database connection available.
	 */
	public function skip() {
		parent::connect($this->_connection);
		$this->skipIf(!$this->with(['MongoDb', 'CouchDb']));
	}

	public function setUp() {
		Galleries::config(['meta' => ['connection' => 'test']]);
	}

	public function tearDown() {
		Galleries::remove();
		Galleries::reset();
	}

	/**
	 * Tests that a successful find on an empty collection doesn't error out
	 * when using count on the resulting collection returned. See issue #1042.
	 */
	public function testFindOnEmptyCollection() {
		$result = Galleries::find('all');

		$expected = 0;
		$result = $result->count();
		$this->assertIdentical($expected, $result);
	}

	public function testUpdateWithNewArray() {
		$new = Galleries::create(['name' => 'Poneys', 'active' => true]);

		$expected = ['name' => 'Poneys', 'active' => true];
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$new->foo = ['bar'];
		$expected = ['name' => 'Poneys', 'active' => true, 'foo' => ['bar']];
		$result = $new->data();
		$this->assertEqual($expected, $result);

		$this->assertTrue($new->save());

		$updated = Galleries::find((string) $new->_id);
		$expected = 'bar';
		$result = $updated->foo[0];
		$this->assertEqual($expected, $result);

		$updated->foo[1] = 'baz';

		$this->assertTrue($updated->save());

		$updated = Galleries::find((string) $updated->_id);
		$expected = 'baz';
		$result = $updated->foo[1];
		$this->assertEqual($expected, $result);
	}
}

?>