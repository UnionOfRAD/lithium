<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\g11n\catalog;

use lithium\tests\mocks\g11n\catalog\MockAdapter;

class AdapterTest extends \lithium\test\Unit {

	public $adapter;

	public function setUp() {
		$this->adapter = new MockAdapter();
	}

	public function testReadStubbed() {
		$result = $this->adapter->read(null, null, null);
		$this->assertNull($result);
	}

	public function testWriteStubbed() {
		$result = $this->adapter->write(null, null, null, []);
		$this->assertFalse($result);
	}

	public function testMergeSkipNoId() {
		$item = [
			'ids' => ['singular' => 'test']
		];
		$expected = [];
		$result = $this->adapter->merge([], $item);
		$this->assertEqual($expected, $result);

		$item = [
			'translated' => ['test']
		];
		$expected = [];
		$result = $this->adapter->merge([], $item);
		$this->assertEqual($expected, $result);

		$item = [
			'id' => null
		];
		$expected = [];
		$result = $this->adapter->merge([], $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeAcceptsSpecialIds() {
		$item = [
			'id' => '0'
		];
		$result = $this->adapter->merge([], $item);
		$this->assertTrue(isset($result['0']));

		$item = [
			'id' => false
		];
		$result = $this->adapter->merge([], $item);
		$this->assertTrue(isset($result[false]));

		$item = [
			'id' => 0
		];
		$result = $this->adapter->merge([], $item);
		$this->assertTrue(isset($result[0]));

		$item = [
			'id' => 536
		];
		$result = $this->adapter->merge([], $item);
		$this->assertTrue(isset($result[536]));

		$item = [
			'id' => ''
		];
		$result = $this->adapter->merge([], $item);
		$this->assertTrue(isset($result['']));
	}

	public function testMergeTranslatedFillIn() {
		$item = [
			'id' => 'test'
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'translated' => ['a']
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => ['a'],
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);

		$item = [
			'id' => 'test'
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'translated' => 'a'
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => 'a',
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeTranslatedDoNotOverwriteNonArrays() {
		$item = [
			'id' => 'test',
			'translated' => 'a'
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'translated' => 'b'
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => 'a',
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeTranslatedUnionArrays() {
		$item = [
			'id' => 'test',
			'translated' => ['a']
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'translated' => ['b']
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => ['a'],
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);

		$item = [
			'id' => 'test',
			'translated' => ['a']
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'translated' => ['b', 'c']
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => ['a', 'c'],
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeTranslatedCastToArray() {
		$item = [
			'id' => 'test',
			'translated' => 'a'
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'translated' => ['b', 'c']
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => ['a', 'c'],
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeEnsureDefaultFormat() {
		$item = [
			'id' => 'test'
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => null,
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge([], $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeIds() {
		$item = [
			'id' => 'test',
			'ids' => ['singular' => 'a']
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'ids' => ['singular' => 'X', 'plural' => 'b']
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => ['singular' => 'X', 'plural' => 'b'],
				'translated' => null,
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeFlags() {
		$item = [
			'id' => 'test',
			'flags' => ['fuzzy' => true]
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'flags' => ['fuzzy' => false]
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => null,
				'flags' => ['fuzzy' => false],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);

		$item = [
			'id' => 'test',
			'flags' => ['fuzzy' => false]
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'flags' => ['fuzzy' => true]
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => null,
				'flags' => ['fuzzy' => true],
				'comments' => [],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeComments() {
		$item = [
			'id' => 'test',
			'comments' => ['a']
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'comments' => ['b']
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => null,
				'flags' => [],
				'comments' => ['a', 'b'],
				'occurrences' => []
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeOccurrences() {
		$item = [
			'id' => 'test',
			'occurrences' => [['file' => 'a.php', 'line' => 2]]
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'occurrences' => [['file' => 'b.php', 'line' => 55]]
		];
		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => [],
				'translated' => null,
				'flags' => [],
				'comments' => [],
				'occurrences' => [
					['file' => 'a.php', 'line' => 2],
					['file' => 'b.php', 'line' => 55]
				]
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);
	}

	public function testMergeWithContexts() {
		$item = [
			'id' => 'test',
			'ids' => ['singular' => 'a']
		];
		$data = $this->adapter->merge([], $item);

		$item = [
			'id' => 'test',
			'ids' => ['singular' => 'X', 'plural' => 'b']
		];
		$data = $this->adapter->merge($data, $item);

		$item = [
			'id' => 'test',
			'ids' => ['singular' => 'a'],
			'context' => 'A'
		];
		$data = $this->adapter->merge($data, $item);

		$item = [
			'id' => 'test',
			'ids' => ['singular' => 'X', 'plural' => 'b'],
			'context' => 'A'
		];
		$data = $this->adapter->merge($data, $item);

		$item = [
			'id' => 'test',
			'ids' => ['singular' => 'a'],
			'context' => 'B'
		];
		$data = $this->adapter->merge($data, $item);

		$item = [
			'id' => 'test',
			'ids' => ['singular' => 'X', 'plural' => 'b'],
			'context' => 'B'
		];
		$data = $this->adapter->merge($data, $item);

		$expected = [
			'test' => [
				'id' => 'test',
				'ids' => ['singular' => 'X', 'plural' => 'b'],
				'translated' => null,
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			],
			'test|A' => [
				'id' => 'test',
				'ids' => ['singular' => 'X', 'plural' => 'b'],
				'translated' => null,
				'flags' => [],
				'comments' => [],
				'occurrences' => [],
				'context' => 'A'
			],
			'test|B' => [
				'id' => 'test',
				'ids' => ['singular' => 'X', 'plural' => 'b'],
				'translated' => null,
				'flags' => [],
				'comments' => [],
				'occurrences' => [],
				'context' => 'B'
			]
		];
		$result = $this->adapter->merge($data, $item);
		$this->assertEqual($expected, $result);
	}
}

?>