<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n\catalog\adapters;

use \Exception;
use \lithium\g11n\catalog\adapters\Cldr;

class CldrTest extends \lithium\test\Unit {

	public $adapter;

	/**
	 * Skip the test if data needed by the adapter cannot be found.
	 *
	 * @return void
	 */
	public function skip() {
		$available = is_dir(LITHIUM_APP_PATH . '/resources/cldr');
		$this->skipIf(!$available, 'The data for needed by the cldr adapter cannot be found.');
	}

	public function setUp() {
		$path = LITHIUM_APP_PATH . '/resources/cldr';
		$this->adapter = new Cldr(compact('path'));
	}

	public function testRead() {
		$result = $this->adapter->read('list.language', 'de', null);
		$this->assertEqual($result['be'], 'Weißrussisch');
		$this->assertEqual($result['en'], 'Englisch');
		$this->assertEqual($result['fr'], 'Französisch');

		$result = $this->adapter->read('list.language', 'de_CH', null);
		$this->assertEqual($result['be'], 'Weissrussisch');

		$result = $this->adapter->read('list.script', 'de', null);
		$this->assertEqual($result['Cher'], 'Cherokee');
		$this->assertEqual($result['Hans'], 'Vereinfachte Chinesische Schrift');

		$result = $this->adapter->read('list.territory', 'de', null);
		$this->assertEqual($result['US'], 'Vereinigte Staaten');
		$this->assertEqual($result['FR'], 'Frankreich');

		$result = $this->adapter->read('list.currency', 'de', null);
		$this->assertEqual($result['DKK'], 'Dänische Krone');
		$this->assertEqual($result['USD'], 'US-Dollar');
		$this->assertEqual($result['EUR'], 'Euro');

		$result = $this->adapter->read('validation.postalCode', 'en_CA', null);
		$this->assertEqual('/^[ABCEGHJKLMNPRSTVXY]\d[A-Z][ ]?\d[A-Z]\d$/', $result);

		$result = $this->adapter->read('validation.postalCode', 'en', null);
		$this->assertNull($result);
	}
}

?>