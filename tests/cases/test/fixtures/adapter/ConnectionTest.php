<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\test\fixtures\adapter;

use lithium\test\fixtures\adapter\Connection;

class ConnectionTest extends \lithium\test\Unit {

	protected $_connection = 'fixture_test';

	protected $_adapter;

	public function setUp() {
		$this->_adapter = new Connection([
			'connection' => $this->_connection,
			'alters' => [
				'add' => ['custom' => ['type' => 'string']]
			],
			'fixtures' => [
				'gallery' => 'lithium\tests\mocks\core\MockLogCall',
				'image' => 'lithium\tests\mocks\core\MockLogCall'
			]
		]);
	}

	public function testInitMissingConnection() {
		$this->assertException("The `'connection'` option must be set.", function() {
			new Connection();
		});
	}

	public function testInstantiateFixture() {
		$callable = $this->_adapter->get('gallery');
		$expected = [
			'connection' => $this->_connection,
			'alters' => ['add' => ['custom' => ['type' => 'string']]]
		];
		$this->assertEqual($expected, $callable->construct[0]);
	}

	public function testMissingFixture() {
		$this->assertException("Undefined fixture named: `foo`.", function() {
			$this->_adapter->get('foo');
		});
	}

	public function testCreate() {
		$this->_adapter->create();

		$callable = $this->_adapter->get('image');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[0]);
	}

	public function testCreateSingle() {
		$this->_adapter->create(['gallery']);

		$callable = $this->_adapter->get('image');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual(0, count($callable->call));

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[0]);
	}

	public function testDrop() {
		$this->_adapter->create();
		$this->_adapter->drop();

		$callable = $this->_adapter->get('image');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('image');
		$expected = ['method' => 'drop', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[1]);

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'drop', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[1]);
	}

	public function testDropLoadedFixtureOnly() {
		$this->_adapter->create(['gallery']);
		$this->_adapter->drop();

		$callable = $this->_adapter->get('image');
		$this->assertEqual(0, count($callable->call));

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'drop', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[1]);
	}

	public function testTruncate() {
		$this->_adapter->create();
		$this->_adapter->truncate();

		$callable = $this->_adapter->get('image');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('image');
		$expected = ['method' => 'truncate', 'params' => []];
		$this->assertEqual($expected, $callable->call[1]);

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'truncate', 'params' => []];
		$this->assertEqual($expected, $callable->call[1]);
	}

	public function testTruncateLoadedFixtureOnly() {
		$this->_adapter->create(['gallery']);
		$this->_adapter->truncate();

		$callable = $this->_adapter->get('image');
		$this->assertEqual(0, count($callable->call));

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'create', 'params' => [true]];
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = ['method' => 'truncate', 'params' => []];
		$this->assertEqual($expected, $callable->call[1]);
	}
}
?>
