<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\g11n;

use lithium\g11n\Catalog;
use lithium\g11n\catalog\adapter\Memory;

class CatalogTest extends \lithium\test\Unit {

	protected $_backup = [];

	public function setUp() {
		$this->_backup['catalogConfig'] = Catalog::config();
		Catalog::reset();
		Catalog::config([
			'runtime' => ['adapter' => new Memory()]
		]);
	}

	public function tearDown() {
		Catalog::reset();
		Catalog::config($this->_backup['catalogConfig']);
	}

	/**
	 * Tests for values returned by `read()`.
	 */
	public function testRead() {
		$result = Catalog::read('runtime', 'validation.ssn', 'de_DE');
		$this->assertNull($result);
	}

	/**
	 * Tests for values returned by `write()`.
	 */
	public function testWrite() {
		$data = [
			'DKK' => 'Dänische Krone'
		];
		$result = Catalog::write('runtime', 'currency', 'de_DE', $data);
		$this->assertTrue($result);
	}

	/**
	 * Tests writing and reading for single and multiple items.
	 */
	public function testWriteRead() {
		$data = '/postalCode en_US/';
		Catalog::write('runtime', 'validation.postalCode', 'en_US', $data);
		$result = Catalog::read('runtime', 'validation.postalCode', 'en_US');
		$this->assertEqual($data, $result);

		$this->tearDown();
		$this->setUp();

		$data = [
			'GRD' => 'Griechische Drachme',
			'DKK' => 'Dänische Krone'
		];
		Catalog::write('runtime', 'currency', 'de', $data);
		$result = Catalog::read('runtime', 'currency', 'de');
		$this->assertEqual($data, $result);
	}

	/**
	 * Tests writing and reading with data merged between cascaded locales.
	 *
	 * Only complete items are merged in, (atomic) merging between items
	 * should not occur. Categories fall back to results for more generic locales.
	 */
	public function testWriteReadMergeLocales() {
		$data = '/postalCode en/';
		Catalog::write('runtime', 'validation.postalCode', 'en', $data);
		$result = Catalog::read('runtime', 'validation.postalCode', 'en_US');
		$expected = '/postalCode en/';
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = '/postalCode en_US/';
		Catalog::write('runtime', 'validation.postalCode', 'en_US', $data);
		$data = '/postalCode en/';
		Catalog::write('runtime', 'validation.postalCode', 'en', $data);
		$result = Catalog::read('runtime', 'validation.postalCode', 'en_US');
		$expected = '/postalCode en_US/';
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = ['a' => true, 'b' => true, 'c' => true];
		Catalog::write('runtime', 'language', 'en', $data);
		$result = Catalog::read('runtime', 'language', 'en_US');
		$expected = ['a' => true, 'b' => true, 'c' => true];
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = [
			'DKK' => 'Dänische Krone'
		];
		Catalog::write('runtime', 'currency', 'de', $data);
		$data = [
			'GRD' => 'Griechische Drachme'
		];
		Catalog::write('runtime', 'currency', 'de_CH', $data);
		$result = Catalog::read('runtime', 'currency', 'de_CH');
		$expected = [
			'GRD' => 'Griechische Drachme',
			'DKK' => 'Dänische Krone'
		];
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = [
			'GRD' => 'de Griechische Drachme',
			'DKK' => 'de Dänische Krone'
		];
		Catalog::write('runtime', 'currency', 'de', $data);
		$data = [
			'GRD' => 'de_CH Griechische Drachme'
		];
		Catalog::write('runtime', 'currency', 'de_CH', $data);
		$result = Catalog::read('runtime', 'currency', 'de_CH');
		$expected = [
			'GRD' => 'de_CH Griechische Drachme',
			'DKK' => 'de Dänische Krone'
		];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that a scope is honored if one is used.
	 */
	public function testWriteReadWithScope() {
		$data = '/postalCode en_US scope0/';
		Catalog::write('runtime', 'validation.postalCode', 'en_US', $data, [
			'scope' => 'scope0'
		]);
		$data = '/postalCode en_US scope1/';
		Catalog::write('runtime', 'validation.postalCode', 'en_US', $data, [
			'scope' => 'scope1'
		]);

		$result = Catalog::read('runtime', 'validation.postalCode', 'en_US');
		$this->assertNull($result);

		$result = Catalog::read('runtime', 'validation.postalCode', 'en_US', [
			'scope' => 'scope0'
		]);
		$expected = '/postalCode en_US scope0/';
		$this->assertEqual($expected, $result);

		$result = Catalog::read('runtime', 'validation.postalCode', 'en_US', [
			'scope' => 'scope1'
		]);
		$expected = '/postalCode en_US scope1/';
		$this->assertEqual($expected, $result);

		$data = '/postalCode en_US/';
		Catalog::write('runtime', 'validation.postalCode', 'en_US', $data);

		$result = Catalog::read('runtime', 'validation.postalCode', 'en_US');
		$expected = '/postalCode en_US/';
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests reading from all configured stores with fallbacks.
	 */
	public function testWriteReadMergeAllConfigurations() {
		Catalog::reset();
		Catalog::config([
			'runtime0' => ['adapter' => new Memory()],
			'runtime1' => ['adapter' => new Memory()]
		]);

		$data = '/postalCode en0/';
		Catalog::write('runtime0', 'validation.postalCode', 'en', $data);
		$data = '/postalCode en_US1/';
		Catalog::write('runtime1', 'validation.postalCode', 'en_US', $data);
		$data = '/postalCode en1/';
		Catalog::write('runtime1', 'validation.postalCode', 'en', $data);
		$result = Catalog::read(true, 'validation.postalCode', 'en_US');
		$expected = '/postalCode en_US1/';
		$this->assertEqual($expected, $result);

		Catalog::reset();
		Catalog::config([
			'runtime0' => ['adapter' => new Memory()],
			'runtime1' => ['adapter' => new Memory()]
		]);

		$data = [
			'GRD' => 'de0 Griechische Drachme',
			'DKK' => 'de0 Dänische Krone'
		];
		Catalog::write('runtime0', 'currency', 'de', $data);
		$data = [
			'GRD' => 'de1 Griechische Drachme'
		];
		Catalog::write('runtime1', 'currency', 'de', $data);
		$data = [
			'GRD' => 'de_CH1 Griechische Drachme'
		];
		Catalog::write('runtime1', 'currency', 'de_CH', $data);
		$result = Catalog::read(true, 'currency', 'de_CH');
		$expected = [
			'GRD' => 'de_CH1 Griechische Drachme',
			'DKK' => 'de0 Dänische Krone'
		];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests reading from selected multiple configured stores.
	 */
	public function testReadMergeSelectedConfigurations() {
		Catalog::reset();
		Catalog::config([
			'runtime0' => ['adapter' => new Memory()],
			'runtime1' => ['adapter' => new Memory()],
			'runtime2' => ['adapter' => new Memory()]
		]);

		$data = '/postalCode en0/';
		Catalog::write('runtime0', 'validation.postalCode', 'en', $data);
		$data = '/postalCode en1/';
		Catalog::write('runtime1', 'validation.postalCode', 'en', $data);
		$data = '/postalCode en2/';
		Catalog::write('runtime2', 'validation.postalCode', 'en', $data);
		$data = '/ssn en2/';
		Catalog::write('runtime2', 'validation.ssn', 'en', $data);

		$result = Catalog::read('runtime0', 'validation.postalCode', 'en');
		$expected = '/postalCode en0/';
		$this->assertEqual($expected, $result);

		$result = Catalog::read('runtime2', 'validation.postalCode', 'en');
		$expected = '/postalCode en2/';
		$this->assertEqual($expected, $result);

		$result = Catalog::read('runtime2', 'validation.postalCode', 'en');
		$expected = '/postalCode en2/';
		$this->assertEqual($expected, $result);

		$result = Catalog::read(['runtime0', 'runtime2'], 'validation', 'en');
		$expected = [
			'postalCode' => '/postalCode en0/',
			'ssn' => '/ssn en2/'
		];
		$this->assertEqual($expected, $result);

		$resultA = Catalog::read(['runtime0', 'runtime2'], 'validation', 'en');
		$resultB = Catalog::read(true, 'validation', 'en');
		$this->assertEqual($resultA, $resultB);
	}

	/**
	 * Tests writing, then reading different types of values.
	 */
	public function testDataTypeSupport() {
		$data = function($n) { return $n === 1 ? 0 : 1; };
		Catalog::write('runtime', 'message.pluralRule', 'en', $data);
		$result = Catalog::read('runtime', 'message.pluralRule', 'en');
		$this->assertEqual($data, $result);

		$data = ['fish', 'fishes'];
		Catalog::write('runtime', 'message.fish', 'en', $data);
		$result = Catalog::read('runtime', 'message.fish', 'en');
		$this->assertEqual($data, $result);
	}

	/**
	 * Tests if the output is normalized and doesn't depend on the input format.
	 */
	public function testInputFormatNormalization() {
		$data = ['house' => 'Haus'];
		Catalog::write('runtime', 'message', 'de', $data);
		$result = Catalog::read('runtime', 'message', 'de', ['lossy' => false]);
		$expected = ['house' => [
			'id' => 'house',
			'ids' => [],
			'translated' => 'Haus',
			'flags' => [],
			'comments' => [],
			'occurrences' => []
		]];
		$this->assertEqual($expected, $result);

		$data = ['house' => [
			'id' => 'house',
			'ids' => [],
			'translated' => 'Haus',
			'flags' => [],
			'comments' => [],
			'occurrences' => []
		]];
		Catalog::write('runtime', 'message', 'de', $data);
		$result = Catalog::read('runtime', 'message', 'de', ['lossy' => false]);
		$expected = ['house' => [
			'id' => 'house',
			'ids' => [],
			'translated' => 'Haus',
			'flags' => [],
			'comments' => [],
			'occurrences' => []
		]];
		$this->assertEqual($expected, $result);
	}

	public function testOutputLossyFormat() {
		$data = ['house' => [
			'id' => 'house',
			'ids' => ['singular' => 'house'],
			'translated' => 'Haus',
			'flags' => [],
			'comments' => [],
			'occurrences' => []
		]];
		Catalog::write('runtime', 'message', 'de', $data);
		$result = Catalog::read('runtime', 'message', 'de');
		$expected = ['house' => 'Haus'];
		$this->assertEqual($expected, $result);
	}

	public function testOutputLosslessFormat() {
		$data = ['house' => [
			'id' => 'house',
			'ids' => ['singular' => 'house'],
			'translated' => 'Haus',
			'flags' => [],
			'comments' => [],
			'occurrences' => []
		]];
		Catalog::write('runtime', 'message', 'de', $data);
		$result = Catalog::read('runtime', 'message', 'de', ['lossy' => false]);
		$expected = ['house' => [
			'id' => 'house',
			'ids' => ['singular' => 'house'],
			'translated' => 'Haus',
			'flags' => [],
			'comments' => [],
			'occurrences' => []
		]];
		$this->assertEqual($expected, $result);
	}

	public function testInvalidWrite() {
		Catalog::reset();

		$this->assertException("Configuration `runtime` has not been defined.", function() {
			$data = ['house' => ['id' => 'house']];
			Catalog::write('runtime', 'message', 'de', $data);
		});
	}
}

?>