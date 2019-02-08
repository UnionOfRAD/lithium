<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\g11n;

use lithium\g11n\Catalog;
use lithium\g11n\catalog\adapter\Memory;
use lithium\util\Inflector;

class CatalogInflectorTest extends \lithium\test\Integration {

	protected $_backup = [];

	public function setUp() {
		$this->_backup['catalogConfig'] = Catalog::config();
		Catalog::config([
			'runtime' => ['adapter' => new Memory()]
		]);
	}

	public function tearDown() {
		Inflector::reset();
		Catalog::reset();
		Catalog::config($this->_backup['catalogConfig']);
	}

	public function testTransliteration() {
		$data = [
			'transliteration' => [
				'\$' => 'dollar',
				'&' => 'and'
			]
		];
		Catalog::write('runtime', 'inflection', 'en', $data);

		Inflector::rules(
			'transliteration', Catalog::read('runtime', 'inflection.transliteration', 'en')
		);

		$result = Inflector::slug('this & that');
		$expected = 'this-and-that';
		$this->assertEqual($expected, $result);

		$data = [
			'transliteration' => [
				't' => 'd',
				'&' => 'und'
			]
		];
		Catalog::write('runtime', 'inflection', 'de', $data);

		Inflector::rules(
			'transliteration', Catalog::read('runtime', 'inflection.transliteration', 'de')
		);

		$result = Inflector::slug('this & that');
		$expected = 'dhis-und-dhad';
		$this->assertEqual($expected, $result);
	}
}

?>