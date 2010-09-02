<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use \lithium\data\Connections;

class MockCouchModel extends \lithium\data\Model {
	protected $_schema = array(
		'someKey' => array()
	);
}

class CouchDbIntegrationTest extends \lithium\test\Integration {

	public function setUp() {
		MockCouchModel::meta(array('connection' => 'test'));
	}

	public function tearDown() {
		$results = MockCouchModel::all();
		if ($results->count()) {
			$results->delete();
		}
	}

	/**
	 * Skip the test if no `test` CouchDb connection available.
	 *
	 * @return void
	 */
	public function skip() {
		$isAvailable = (
			Connections::get('test', array('config' => true)) &&
			Connections::get('test')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available");

		$couchConnection = strpos(get_class(Connections::get('test')), 'CouchDb');
		$this->skipIf(!$couchConnection, "Test connection is not CouchDb");
	}

	public function testCreate() {
		$result = MockCouchModel::create()->save();
		$this->assertTrue($result);
	}

	public function testSavingIdWithLockedModel() {
		$id = 'myCustomId';
		$model = MockCouchModel::create(compact('id'));
		$result = $model->save();

		$this->assertTrue($result);

		$data = $model->data();
		$expected = $id;
		$this->assertNotEqual($expected, $data['id']);
	}

	public function testSavingIdWithUnlockedModel() {
		MockCouchModel::meta(array('locked' => false));
		$id = 'myCustomId';
		$model = MockCouchModel::create(compact('id'));
		$result = $model->save();
		$this->assertTrue($result);

		$data = $model->data();
		$expected = $id;
		$this->assertEqual($expected, $data['id']);
	}

	public function testUpdate() {
		$model = MockCouchModel::create(array('someKey' => 'someValue'));
		$result = $model->save();
		$this->assertTrue($result);

		$data = $model->data();

		$this->assertTrue(array_key_exists('id', $data));
		$this->assertTrue(array_key_exists('rev', $data));

		$expected = 'someValue';
		$this->assertEqual($expected, $data['someKey']);

		$model->someKey = 'someOtherValue';
		$result = $model->save();
		$this->assertTrue($result);

		$updated = $model->data();

		$expected = 'someOtherValue';
		$this->assertEqual($expected, $updated['someKey']);
		$this->assertEqual($data['id'], $updated['id']);
		$this->assertNotEqual($data['rev'], $updated['rev']);
	}

}

?>