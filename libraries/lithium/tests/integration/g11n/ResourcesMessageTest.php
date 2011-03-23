<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\g11n;

use lithium\g11n\Catalog;

/**
 * Test for integration of g11n resources. Numbers of rules refer to those documented in
 * the document on pluralization at Mozilla.
 *
 * @link https://developer.mozilla.org/en/Localization_and_Plurals
 */
class ResourcesMessageTest extends \lithium\test\Unit {

	protected $_backups = array();

	public function setUp() {
		$this->_backups['catalogConfig'] = Catalog::config();
		Catalog::reset();
		Catalog::config(array(
			'lithium' => array(
				'adapter' => 'Php',
				'path' => LITHIUM_LIBRARY_PATH . '/lithium/g11n/resources/php'
			)
		));
	}

	public function tearDown() {
		Catalog::reset();
		Catalog::config($this->_backups['catalogConfig']);
	}

	/**
	 * Tests the plural rule #1 which applies to the following languages
	 * grouped by family and sorted alphabetically.
	 *
	 * Germanic family:
	 * - English (en)
	 *
	 * @return void
	 */
	public function testPlurals1() {
		$locales = array('en');

		foreach ($locales as $locale) {
			$result = Catalog::read('lithium', 'message.pluralForms', $locale);
			$this->assertEqual(2, $result, "Locale: `{$locale}`\n{:message}");

			$rule = Catalog::read('lithium', 'message.pluralRule', $locale);

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
}

?>