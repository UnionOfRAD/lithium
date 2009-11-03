<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use \lithium\data\Connections;

class CompanyIntegration extends \lithium\data\Model {

	protected $_meta = array(
		'connection' => 'test',
		'source' => 'companies'
	);

	public static function classes() {
		return static::_instance()->_classes;
	}
}

class SourceTest extends \lithium\test\Unit {

	/**
	 * Skip the test if no test database connection available.
	 *
	 * @return void
	 */
	public function skip() {
		$isAvailable = (
			Connections::get('test', array('config' => true)) &&
			Connections::get('test')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available");
	}

	/**
	 * Tests that a single record with a manually specified primary key can be created, persisted
	 * to an arbitrary data store, re-read and updated.
	 *
	 * @return void
	 */
	public function testSingleReadWriteWithKey() {
		CompanyIntegration::__init();
		$key = CompanyIntegration::meta('key');
		$classes = CompanyIntegration::classes();

		$new = CompanyIntegration::create(array($key => 12345, 'name' => 'Acme, Inc.'));
		$this->assertTrue(is_a($new, $classes['record']));

		$result = $new->to('array');
		$expected = array($key => 12345, 'name' => 'Acme, Inc.');
		$this->assertEqual($expected, $result);

		$this->assertFalse($new->exists());
		$this->assertTrue($new->save());
		$this->assertTrue($new->exists());

		$existing = CompanyIntegration::find(12345);
		$result = $existing->to('array');
		$this->assertEqual($expected, $result);
		$this->assertTrue($existing->exists());

		$existing->name = 'Big Brother and the Holding Company';
		$this->assertTrue($existing->save());

		$existing = CompanyIntegration::find(12345);
		$result = $existing->to('array');
		$expected['name'] = 'Big Brother and the Holding Company';
		$this->assertEqual($expected, $result);

		$existing->delete();
	}
}

?>