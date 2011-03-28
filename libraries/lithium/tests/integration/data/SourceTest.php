<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use Exception;
use lithium\data\Connections;
use lithium\tests\mocks\data\Company;
use lithium\tests\mocks\data\Employee;

class SourceTest extends \lithium\test\Unit {

	protected $_connection = null;

	protected $_classes = array(
		'employee' => 'lithium\tests\mocks\data\Employee',
		'company' => 'lithium\tests\mocks\data\Company'
	);

	public $companyData = array(
		array('name' => 'StuffMart', 'active' => true),
		array('name' => 'Ma \'n Pa\'s Data Warehousing & Bait Shop', 'active' => false)
	);

	/**
	 * @todo Make less dumb.
	 *
	 */
	public function setUp() {
		Company::config();
		Employee::config();
		$this->_connection = Connections::get('test');

		if (strpos(get_class($this->_connection), 'CouchDb')) {
			$this->_loadViews();
		}

		try {
			foreach (Company::all() as $company) {
				$company->delete();
			}
		} catch (Exception $e) {}
	}

	protected function _loadViews() {
		Company::create()->save();
	}

	/**
	 * @todo Make this less dumb.
	 */
	public function tearDown() {
		try {
			foreach (Company::all() as $company) {
				$company->delete();
			}
		} catch (Exception $e) {
			$this->assertTrue(false, $e->getMessage());
		}
	}

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
		$this->skipIf(!$isAvailable, "No test connection available.");
	}

	/**
	 * Tests that a single record with a manually specified primary key can be created, persisted
	 * to an arbitrary data store, re-read and updated.
	 *
	 * @return void
	 */
	public function testSingleReadWriteWithKey() {
		$key = Company::meta('key');
		$new = Company::create(array($key => 12345, 'name' => 'Acme, Inc.'));

		$result = $new->data();
		$expected = array($key => 12345, 'name' => 'Acme, Inc.');
		$this->assertEqual($expected[$key], $result[$key]);
		$this->assertEqual($expected['name'], $result['name']);

		$this->assertFalse($new->exists());
		$this->assertTrue($new->save());
		$this->assertTrue($new->exists());

		$existing = Company::find(12345);
		$result = $existing->data();
		$this->assertEqual($expected[$key], $result[$key]);
		$this->assertEqual($expected['name'], $result['name']);
		$this->assertTrue($existing->exists());

		$existing->name = 'Big Brother and the Holding Company';
		$result = $existing->save();
		$this->assertTrue($result);

		$existing = Company::find(12345);
		$result = $existing->data();
		$expected['name'] = 'Big Brother and the Holding Company';
		$this->assertEqual($expected[$key], $result[$key]);
		$this->assertEqual($expected['name'], $result['name']);

		$this->assertTrue($existing->delete());
	}

	public function testRewind() {
		$key = Company::meta('key');
		$new = Company::create(array($key => 12345, 'name' => 'Acme, Inc.'));

		$result = $new->data();
		$this->assertTrue($result !== null);
		$this->assertTrue($new->save());
		$this->assertTrue($new->exists());

		$result = Company::all(12345);
		$this->assertTrue($result !== null);

		$result = $result->rewind();
		$this->assertTrue($result !== null);
		$this->assertTrue(!is_string($result));
	}

	public function testFindFirstWithFieldsOption() {
		return;
		$key = Company::meta('key');
		$new = Company::create(array($key => 1111, 'name' => 'Test find first with fields.'));
		$result = $new->data();

		$expected = array($key => 1111, 'name' => 'Test find first with fields.');
		$this->assertEqual($expected['name'], $result['name']);
		$this->assertEqual($expected[$key], $result[$key]);
		$this->assertFalse($new->exists());
		$this->assertTrue($new->save());
		$this->assertTrue($new->exists());

		$result = Company::find('first', array('fields' => array('name')));
		$this->assertFalse(is_null($result));

		$this->skipIf(is_null($result), 'No result returned to test');
		$result = $result->data();
		$this->assertEqual($expected['name'], $result['name']);

		$this->assertTrue($new->delete());
	}

	public function testReadWriteMultiple() {
		$companies = array();
		$key = Company::meta('key');

		foreach ($this->companyData as $data) {
			$companies[] = Company::create($data);
			$this->assertTrue(end($companies)->save());
			$this->assertTrue(end($companies)->{$key});
		}

		$this->assertIdentical(2, Company::count());
		$this->assertIdentical(1, Company::count(array('active' => true)));
		$this->assertIdentical(1, Company::count(array('active' => false)));
		$this->assertIdentical(0, Company::count(array('active' => null)));
		$all = Company::all();
		$this->assertIdentical(2, Company::count());

		$expected = count($this->companyData);
		$this->assertEqual($expected, $all->count());
		$this->assertEqual($expected, count($all));

		$id = (string) $all->first()->{$key};
		$this->assertTrue(strlen($id) > 0);
		$this->assertTrue($all->data());

		foreach ($companies as $company) {
			$this->assertTrue($company->delete());
		}
		$this->assertIdentical(0, Company::count());
	}

	public function testEntityFields() {
		foreach ($this->companyData as $data) {
			Company::create($data)->save();
		}
		$all = Company::all();

		$result = $all->first(function($doc) { return $doc->name == 'StuffMart'; });
		$this->assertEqual('StuffMart', $result->name);

		$result = $result->data();
		$this->assertEqual('StuffMart', $result['name']);

		$result = $all->next();
		$this->assertEqual('Ma \'n Pa\'s Data Warehousing & Bait Shop', $result->name);

		$result = $result->data();
		$this->assertEqual('Ma \'n Pa\'s Data Warehousing & Bait Shop', $result['name']);

		$this->assertNull($all->next());
	}

	/**
	 * Tests that a record can be created, saved, and subsequently re-read using a key
	 * auto-generated by the data source. Uses short-hand `find()` syntax which does not support
	 * compound keys.
	 *
	 * @return void
	 */
	public function testGetRecordByGeneratedId() {
		$key = Company::meta('key');
		$company = Company::create(array('name' => 'Test Company'));
		$this->assertTrue($company->save());

		$id = (string) $company->{$key};
		$companyCopy = Company::find($id)->data();
		$data = $company->data();

		foreach ($data as $key => $value) {
			$this->assertTrue(isset($companyCopy[$key]));
			$this->assertEqual($data[$key], $companyCopy[$key]);
		}
	}

	/**
	 * Tests the default relationship information provided by the backend data source.
	 *
	 * @return void
	 */
	public function testDefaultRelationshipInfo() {
		$connection = $this->_connection;
		$message = "Relationships are not supported by this adapter.";
		$this->skipIf(!$connection::enabled('relationships'), $message);

		$this->assertEqual(array('Employees'), array_keys(Company::relations()));
		$this->assertEqual(array('Company'), array_keys(Employee::relations()));

		$this->assertEqual(array('Employees'), Company::relations('hasMany'));
		$this->assertEqual(array('Company'), Employee::relations('belongsTo'));

		$this->assertFalse(Company::relations('belongsTo'));
		$this->assertFalse(Company::relations('hasOne'));

		$this->assertFalse(Employee::relations('hasMany'));
		$this->assertFalse(Employee::relations('hasOne'));

		$result = Company::relations('Employees');

		$this->assertEqual('hasMany', $result->data('type'));
		$this->assertEqual($this->_classes['employee'], $result->data('to'));
	}

	public function testRelationshipQuerying() {
		$connection = $this->_connection;
		$message = "Relationships are not supported by this adapter.";
		$this->skipIf(!$connection::enabled('relationships'), $message);

		foreach ($this->companyData as $data) {
			Company::create($data)->save();
		}
		$stuffMart = Company::findFirstByName('StuffMart');
		$maAndPas = Company::findFirstByName('Ma \'n Pa\'s Data Warehousing & Bait Shop');

		$this->assertEqual($this->_classes['employee'], $stuffMart->employees->model());
		$this->assertEqual($this->_classes['employee'], $maAndPas->employees->model());

		foreach (array('Mr. Smith', 'Mr. Jones', 'Mr. Brown') as $name) {
			$stuffMart->employees[] = Employee::create(compact('name'));
		}
		$expected = Company::key($stuffMart) + array(
			'name' => 'StuffMart', 'active' => true, 'employees' => array(
				array('name' => 'Mr. Smith'),
				array('name' => 'Mr. Jones'),
				array('name' => 'Mr. Brown')
			)
		);
		$this->assertEqual($expected, $stuffMart->data());
		$this->assertTrue($stuffMart->save());
		$this->assertEqual('Smith', $stuffMart->employees[0]->lastName());

		$stuffMartReloaded = Company::findFirstByName('StuffMart');
		$this->assertEqual('Smith', $stuffMartReloaded->employees[0]->lastName());

		foreach (array('Ma', 'Pa') as $name) {
			$maAndPas->employees[] = Employee::create(compact('name'));
		}
		$maAndPas->save();
	}

	public function testAbstractTypeHandling() {
		$key = Company::meta('key');

		foreach ($this->companyData as $data) {
			$companies[] = Company::create($data);
			$this->assertTrue(end($companies)->save());
			$this->assertTrue(end($companies)->{$key});
		}

		foreach (Company::all() as $company) {
			$this->assertTrue($company->delete());
		}
	}
}

?>