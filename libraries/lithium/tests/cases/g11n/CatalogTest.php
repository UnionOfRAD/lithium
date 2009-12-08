<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n;

use \lithium\g11n\Catalog;
use \lithium\g11n\catalog\adapter\Memory;

class CatalogTest extends \lithium\test\Unit {

	protected $_backups = array();

	public function setUp() {
		$this->_backups['catalogConfig'] = Catalog::config()->to('array');
		Catalog::clear();
		Catalog::config(array(
			'runtime' => array('adapter' => new Memory())
		));
	}

	public function tearDown() {
		Catalog::clear();
		Catalog::config($this->_backups['catalogConfig']);
	}

	/**
	 * Tests configuration.
	 *
	 * @return void
	 */
	public function testConfig() {}

	/**
	 * Tests if configurations are cleared.
	 *
	 * @return void
	 */
	public function testClear() {
		$this->assertTrue(Catalog::config()->count());
		Catalog::clear();
		$this->assertFalse(Catalog::config()->count());
	}

	public function testDescribe() {}

	/**
	 * Tests for values returned by `read()`.
	 *
	 * @return void
	 */
	public function testRead() {
		$result = Catalog::read('validation.ssn', 'de');
		$this->assertNull($result);
	}

	/**
	 * Tests writing and reading for single items and locales as well as
	 * for multiple items and locales. The ouput format should be consistent between
	 * all cases.
	 *
	 * @return void
	 */
	public function testWriteRead() {
		$data = array(
			'en_US' => '/postalCode en_US/'
		);
		Catalog::write('validation.postalCode', $data, array('name' => 'runtime'));
		$result = Catalog::read('validation.postalCode', 'en_US');
		$this->assertEqual($data, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'en_US'	 => '/postalCode en_US/',
			'de_DE' => '/postalCode de_DE/'
		);
		Catalog::write('validation.postalCode', $data, array('name' => 'runtime'));
		$result = Catalog::read('validation.postalCode', array('en_US', 'de_DE'));
		$this->assertEqual($data, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'de' => array(
				'GRD' => 'Griechische Drachme',
				'DKK' => 'Dänische Krone'
		));
		Catalog::write('list.currency', $data, array('name' => 'runtime'));
		$result = Catalog::read('list.currency', 'de');
		$this->assertEqual($data, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'de' => array(
				'GRD' => 'Griechische Drachme',
				'DKK' => 'Dänische Krone'
			),
			'fr' => array(
				'GRD' => 'rachme grecque',
				'DKK' => 'couronne danoise'
			),
			'en' => array(
				'GRD' => 'Greek Drachma',
				'DKK' => 'Danish Krone'
		));
		Catalog::write('list.currency', $data, array('name' => 'runtime'));
		$result = Catalog::read('list.currency', array('fr', 'en'));
		unset($data['de']);
		$this->assertEqual($data, $result);
	}

	/**
	 * Tests writing and reading with data merged between locales. Actual merging happens only
	 * for lists i.e. `message.page`. Only complete items are merged in, (atomic) merging between
	 * items should not occur. Categories like `validation.postalCode` fall back to results
	 * for more generic locales.
	 *
	 * @return void
	 */
	public function testWriteReadMergeLocales() {
		$data = array(
			'en' => '/postalCode en/'
		);
		Catalog::write('validation.postalCode', $data, array('name' => 'runtime'));
		$result = Catalog::read('validation.postalCode', 'en_US');
		$expected = array(
			'en_US' => '/postalCode en/'
		);
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'en_US' => '/postalCode en_US/',
			'en' => '/postalCode en/'
		);
		Catalog::write('validation.postalCode', $data, array('name' => 'runtime'));
		$result = Catalog::read('validation.postalCode', 'en_US');
		$expected = array(
			'en_US' => '/postalCode en_US/'
		);
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'en' => array('a' => true, 'b' => true, 'c' => true)
		);
		Catalog::write('list.language', $data, array('name' => 'runtime'));
		$result = Catalog::read('list.language', 'en_US');
		$expected = array(
			'en_US' => array('a' => true, 'b' => true, 'c' => true)
		);
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'en' => array('a' => true, 'c' => true),
			'en_US' => array('b' => true)
		);
		Catalog::write('list.language', $data, array('name' => 'runtime'));
		$result = Catalog::read('list.language', 'en_US');
		$expected = array(
			'en_US' => array('a' => true, 'b' => true, 'c' => true)
		);
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'de' => array(
				'DKK' => 'Dänische Krone'
		));
		Catalog::write('list.currency', $data, array('name' => 'runtime'));
		$result = Catalog::read('list.currency', 'de_CH');
		$expected = array(
			'de_CH' => array(
				'DKK' => 'Dänische Krone'
		));
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'de' => array(
				'DKK' => 'Dänische Krone'
			),
			'de_CH' => array(
				'GRD' => 'Griechische Drachme',
		));
		Catalog::write('list.currency', $data, array('name' => 'runtime'));
		$result = Catalog::read('list.currency', 'de_CH');
		$expected = array(
			'de_CH' => array(
				'GRD' => 'Griechische Drachme',
				'DKK' => 'Dänische Krone'
		));
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'de' => array(
				'GRD' => 'de Griechische Drachme',
				'DKK' => 'de Dänische Krone'
			),
			'de_CH' => array(
				'GRD' => 'de_CH Griechische Drachme',
		));
		Catalog::write('list.currency', $data, array('name' => 'runtime'));
		$result = Catalog::read('list.currency', 'de_CH');
		$expected = array(
			'de_CH' => array(
				'GRD' => 'de_CH Griechische Drachme',
				'DKK' => 'de Dänische Krone'
		));
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'de' => array(
				'GRD' => 'de Griechische Drachme',
				'DKK' => 'de Dänische Krone'
			),
			'de_CH' => array(
				'DKK' => 'de_CH Dänische Krone'
			),
			'fr' => array(
				'GRD' => 'fr rachme grecque',
				'DKK' => 'fr couronne danoise'
			),
			'fr_CH' => array(
				'GRD' => 'fr_CH rachme grecque',
		));
		Catalog::write('list.currency', $data, array('name' => 'runtime'));
		$result = Catalog::read('list.currency', array('de_CH', 'fr_CH'));
		$expected = array(
			'de_CH' => array(
				'GRD' => 'de Griechische Drachme',
				'DKK' => 'de_CH Dänische Krone'
			),
			'fr_CH' => array(
				'GRD' => 'fr_CH rachme grecque',
				'DKK' => 'fr couronne danoise'
		));
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that a scope is honored if one is used.
	 *
	 * @return void
	 */
	public function testWriteReadWithScope() {
		$data = array(
			'en_US'	 => '/postalCode en_US scope0/'
		);
		Catalog::write('validation.postalCode', $data, array(
			'name' => 'runtime',
			'scope' => 'scope0'
		));
		$data = array(
			'en_US'	 => '/postalCode en_US scope1/'
		);
		Catalog::write('validation.postalCode', $data, array(
			'name' => 'runtime',
			'scope' => 'scope1'
		));

		$result = Catalog::read('validation.postalCode', 'en_US');
		$this->assertNull($result);

		$result = Catalog::read('validation.postalCode', 'en_US', array('scope' => 'scope0'));
		$expected = array(
			'en_US'	 => '/postalCode en_US scope0/'
		);
		$this->assertEqual($expected, $result);

		$result = Catalog::read('validation.postalCode', 'en_US', array('scope' => 'scope1'));
		$expected = array(
			'en_US'	 => '/postalCode en_US scope1/'
		);
		$this->assertEqual($expected, $result);

		$data = array(
			'en_US'	 => '/postalCode en_US/'
		);
		Catalog::write('validation.postalCode', $data, array(
			'name' => 'runtime'
		));

		$result = Catalog::read('validation.postalCode', 'en_US');
		$expected = array(
			'en_US'	 => '/postalCode en_US/'
		);
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests reading from multiple configured stores with fallbacks.  The first result should
	 * be returned for i.e. `validation.postalCode` but for lists or list-like categories
	 * (i.e. `message`) results are being merged.
	 *
	 * @return void
	 */
	public function testWriteReadMergeConfigurations() {
		Catalog::clear();
		Catalog::config(array(
			'runtime0' => array('adapter' => new Memory()),
			'runtime1' => array('adapter' => new Memory())
		));

		$data = array(
			'en' => '/postalCode en0/'
		);
		Catalog::write('validation.postalCode', $data, array('name' => 'runtime0'));
		$data = array(
			'en_US' => '/postalCode en_US1/',
			'en' => '/postalCode en1/'
		);
		Catalog::write('validation.postalCode', $data, array('name' => 'runtime1'));
		$result = Catalog::read('validation.postalCode', 'en_US');
		$expected = array(
			'en_US' => '/postalCode en_US1/'
		);
		$this->assertEqual($expected, $result);

		Catalog::clear();
		Catalog::config(array(
			'runtime0' => array('adapter' => new Memory()),
			'runtime1' => array('adapter' => new Memory())
		));

		$data = array(
			'de' => array(
				'GRD' => 'de0 Griechische Drachme',
				'DKK' => 'de0 Dänische Krone'
		));
		Catalog::write('list.currency', $data, array('name' => 'runtime0'));
		$data = array(
			'de' => array(
				'GRD' => 'de1 Griechische Drachme'
			),
			'de_CH' => array(
				'GRD' => 'de_CH1 Griechische Drachme'
		));
		Catalog::write('list.currency', $data, array('name' => 'runtime1'));
		$result = Catalog::read('list.currency', 'de_CH');
		$expected = array(
			'de_CH' => array(
				'GRD' => 'de_CH1 Griechische Drachme',
				'DKK' => 'de0 Dänische Krone'
		));
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests writing, then reading different types of values.
	 *
	 * @return void
	 */
	public function testDataTypeSupport() {
		$data = array('en' => function($n) { return $n == 1 ? 0 : 1; });
		Catalog::write('message.plural', $data, array('name' => 'runtime'));
		$result = Catalog::read('message.plural', 'en');
		$this->assertEqual($data, $result);
	}

	/**
	 * Tests if the output is normalized and doesn't depend on the input format.
	 *
	 * @return void
	 */
	public function testDataInputOutputFormat() {
		$data = array(
			'de' => array(
				'house'	=> 'Haus'
		));
		Catalog::write('message.page', $data, array('name' => 'runtime'));
		$result = Catalog::read('message.page', 'de');
		$expected = array(
			'de' => array(
				'house'	=> array(
					'singularId' => 'house',
					'pluralId' => null,
					'translated' => array('Haus'),
					'fuzzy' => false,
					'comments' => array(),
					'occurrences' => array()
		)));
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$data = array(
			'de' => array(
				'house'	=> array('Haus')
		));
		Catalog::write('message.page', $data, array('name' => 'runtime'));
		$result = Catalog::read('message.page', 'de');
		$expected = array(
			'de' => array(
				'house'	=> array(
					'singularId' => 'house',
					'pluralId' => null,
					'translated' => array('Haus'),
					'fuzzy' => false,
					'comments' => array(),
					'occurrences' => array()
		)));
		$this->assertEqual($expected, $result);

		$this->tearDown();
		$this->setUp();

		$expected = array(
			'de' => array(
				'house'	=> array(
					'singularId' => 'house',
					'translated' => array('Haus'),
		)));
		Catalog::write('message.page', $data, array('name' => 'runtime'));
		$result = Catalog::read('message.page', 'de');
		$expected = array(
			'de' => array(
				'house'	=> array(
					'singularId' => 'house',
					'pluralId' => null,
					'translated' => array('Haus'),
					'fuzzy' => false,
					'comments' => array(),
					'occurrences' => array()
		)));
		$this->assertEqual($expected, $result);
	}
}

?>