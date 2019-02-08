<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\integration\g11n;

use lithium\core\Libraries;
use lithium\g11n\Catalog;

/**
 * Test for integration of g11n resources. Numbers of rules refer to those documented in
 * the document on pluralization at Mozilla.
 *
 * @link https://developer.mozilla.org/en/Localization_and_Plurals
 */
class ResourcesMessageTest extends \lithium\test\Integration {

	protected $_backup = [];

	public function setUp() {
		$this->_backup['catalogConfig'] = Catalog::config();
		Catalog::config([
			'lithium' => [
				'adapter' => 'Php',
				'path' => Libraries::get('lithium', 'path') . '/g11n/resources/php'
			]
		]);
	}

	public function tearDown() {
		Catalog::reset();
		Catalog::config($this->_backup['catalogConfig']);
	}

	/**
	 * Tests the plural rule #1 which applies to the following languages
	 * grouped by family and sorted alphabetically.
	 *
	 * Germanic family:
	 * - English (en)
	 * - German (de)
	 */
	public function testPlurals1() {
		$locales = [
			'en', 'de'
		];
		foreach ($locales as $locale) {
			$expected = 2;
			$result = Catalog::read(true, 'message.pluralForms', $locale);
			$this->assertEqual($expected, $result, "Locale: `{$locale}`\n{:message}");

			$rule = Catalog::read(true, 'message.pluralRule', $locale);

			$expected  = '10111111111111111111111111111111111111111111111111';
			$expected .= '11111111111111111111111111111111111111111111111111';
			$expected .= '11111111111111111111111111111111111111111111111111';
			$expected .= '11111111111111111111111111111111111111111111111111';
			$result = '';

			for ($n = 0; $n < 200; $n++) {
				$result .= $rule($n);
			}
			$this->assertIdentical($expected, $result, "Locale: `{$locale}`\n{:message}");
		}
	}

	/**
	 * Tests the plural rule #2 which applies to the following languages
	 * grouped by family and sorted alphabetically.
	 *
	 * Romanic family:
	 * - French (fr)
	 */
	public function testPlurals2() {
		$locales = [
			'fr'
		];
		foreach ($locales as $locale) {
			$expected = 2;
			$result = Catalog::read(true, 'message.pluralForms', $locale);
			$this->assertEqual($expected, $result, "Locale: `{$locale}`\n{:message}");

			$rule = Catalog::read(true, 'message.pluralRule', $locale);

			$expected  = '00111111111111111111111111111111111111111111111111';
			$expected .= '11111111111111111111111111111111111111111111111111';
			$expected .= '11111111111111111111111111111111111111111111111111';
			$expected .= '11111111111111111111111111111111111111111111111111';
			$result = '';

			for ($n = 0; $n < 200; $n++) {
				$result .= $rule($n);
			}
			$this->assertIdentical($expected, $result, "Locale: `{$locale}`\n{:message}");
		}
	}
}

?>