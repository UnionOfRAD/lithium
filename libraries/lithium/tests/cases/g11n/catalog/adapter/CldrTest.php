<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n\catalog\adapter;

use \Exception;
use \lithium\g11n\catalog\adapter\Cldr;

class CldrTest extends \lithium\test\Unit {

	public $adapter;

	/**
	 * Skip the test if data needed by the adapter cannot be found.
	 *
	 * @return void
	 */
	public function skip() {
		$available = is_dir(LITHIUM_APP_PATH . '/resources/g11n/cldr');
		$this->skipIf(!$available, 'The data for needed by the cldr adapter cannot be found.');
	}

	public function setUp() {
		$path = LITHIUM_APP_PATH . '/resources/g11n/cldr';
		$this->adapter = new Cldr(compact('path'));
	}

	public function testRead() {
		$result = $this->adapter->read('language', 'de', null);

		$this->assertEqual($result['be']['translated'], 'Weißrussisch');
		$this->assertEqual($result['en']['translated'], 'Englisch');
		$this->assertEqual($result['fr']['translated'], 'Französisch');

		$result = $this->adapter->read('language', 'de_CH', null);
		$this->assertEqual($result['be']['translated'], 'Weissrussisch');

		$result = $this->adapter->read('script', 'de', null);
		$this->assertEqual($result['Cher']['translated'], 'Cherokee');
		$this->assertEqual($result['Hans']['translated'], 'Vereinfachte Chinesische Schrift');

		$result = $this->adapter->read('territory', 'de', null);
		$this->assertEqual($result['US']['translated'], 'Vereinigte Staaten');
		$this->assertEqual($result['FR']['translated'], 'Frankreich');

		$result = $this->adapter->read('currency', 'de', null);
		$this->assertEqual($result['DKK']['translated'], 'Dänische Krone');
		$this->assertEqual($result['USD']['translated'], 'US-Dollar');
		$this->assertEqual($result['EUR']['translated'], 'Euro');

		$result = $this->adapter->read('validation', 'en_CA', null);
		$expected = '/^[ABCEGHJKLMNPRSTVXY]\d[A-Z][ ]?\d[A-Z]\d$/';
		$this->assertEqual($result['postalCode']['translated'], $expected);
	}
}

?>