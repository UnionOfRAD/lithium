<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\source\http\adapter;

use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\data\entity\Document;
use lithium\data\source\http\adapter\CouchDb;
use lithium\tests\mocks\data\source\http\adapter\MockCouchPost;

class CouchDbTest extends \lithium\test\Unit {

	protected $_db = null;

	protected $_query;

	protected $_testConfig = [
		'database' => 'lithium-test',
		'persistent' => false,
		'scheme' => 'tcp',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'port' => 80,
		'timeout' => 2,
		'socket' => 'lithium\tests\mocks\data\source\http\adapter\MockSocket'
	];

	protected $_model = 'lithium\tests\mocks\data\source\http\adapter\MockCouchPost';

	public function setUp() {
		$this->_db = new CouchDb(['socket' => false]);
		$model = $this->_model;
		Connections::add('mockconn', ['object' => $this->_db]);
		$model::config(['meta' => ['connection' => 'mockconn']]);
		$model::resetSchema();

		$entity = new Document(compact('model'));
		$type = 'create';
		$this->_query = new Query(compact('model', 'entity', 'type'));
	}

	public function tearDown() {
		Connections::remove('mockconn');
		MockCouchPost::reset();
	}
	public function testAllMethodsNoConnection() {
		$this->assertNotEmpty($this->_db->connect());
		$this->assertNotEmpty($this->_db->disconnect());
		$this->assertEmpty($this->_db->get());
		$this->assertEmpty($this->_db->post());
		$this->assertEmpty($this->_db->put());
	}

	public function testConnect() {
		$this->_db = new CouchDb($this->_testConfig);
		$result = $this->_db->connect();
		$this->assertTrue($result);
	}

	public function testDisconnect() {
		$couchdb = new CouchDb($this->_testConfig);
		$couchdb->connect();

		$result = $couchdb->disconnect();
		$this->assertTrue($result);
	}

	public function testSources() {
		$couchdb = new CouchDb($this->_testConfig);
		$result = $couchdb->sources();
		$this->assertNull($result);
	}

	public function testDescribe() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->assertInternalType('object', $couchdb->describe('companies'));
	}

	public function testEntityItem() {
		$couchdb = new CouchDb($this->_testConfig);
		$data = ['_id' => 'a1', '_rev' => '1-2', 'author' => 'author 1', 'body' => 'body 1'];
		$expected = [
			'id' => 'a1', 'rev' => '1-2', 'author' => 'author 1', 'body' => 'body 1'
		];

		$item = $couchdb->item($this->_query->model(), $data);
		$result = $item->data();
		$this->assertEqual($expected, $result);

		$data = ['author' => 'author 1', 'body' => 'body 1'];
		$expected = [
			'author' => 'author 1', 'body' => 'body 1'
		];

		$item = $couchdb->item($this->_query->model(), $data);
		$result = $item->data();
		$this->assertEqual($expected, $result);
	}

	public function testSetItem() {
		$couchdb = new CouchDb($this->_testConfig);
		$expected = [
			'id' => 'a1', 'rev' => '1-2', 'author' => 'author 1', 'body' => 'body 1'
		];
		$data = [[
			'_id' => 'a1', '_rev' => '1-2', 'author' => 'author 1', 'body' => 'body 1']
		];
		$item = $couchdb->item($this->_query->model(), $data, ['class' => 'set']);
		$this->assertCount(1, $item);
		$result = $item->first()->data();
		$this->assertEqual($expected, $result);
	}

	public function testCreateNoId() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->_query->data(['name' => 'Acme Inc.']);

		$result = $couchdb->create($this->_query);
		$this->assertTrue($result);

		$expected = '/lithium-test';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = [];
		$result = $couchdb->last->request->query;
		$this->assertEqual($expected, $result);
	}

	public function testCreateWithId() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->_query->data(['id' => 12345, 'name' => 'Acme Inc.']);

		$result = $couchdb->create($this->_query);
		$this->assertTrue($result);

		$expected = '/lithium-test/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = [];
		$result = $couchdb->last->request->query;
		$this->assertEqual($expected, $result);
	}

	public function testReadNoConditions() {
		$couchdb = new CouchDb($this->_testConfig);

		$result = $couchdb->read($this->_query);
		$this->assertNotEmpty($result);
		$this->assertEqual(['total_rows' => 3, 'offset' => 0], $result->stats());

		$expected = '/lithium-test/_all_docs';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = '?include_docs=true';
		$result = $couchdb->last->request->queryString();
		$this->assertEqual($expected, $result);
	}

	public function testReadWithConditions() {
		$couchdb = new CouchDb($this->_testConfig);

		$this->_query->conditions(['id' => 12345]);
		$result = $couchdb->read($this->_query);
		$this->assertNotEmpty($result);

		$expected = '/lithium-test/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = '';
		$result = $couchdb->last->request->queryString();
		$this->assertEqual($expected, $result);

		$this->_query->conditions(['id' => 12345, 'path' => '/lithium-test/12345']);
		$result = $couchdb->read($this->_query);
		$this->assertNotEmpty($result);
	}

	public function testReadWithViewConditions() {
		$couchdb = new CouchDb($this->_testConfig);

		$this->_query->conditions([
			'design' => 'latest', 'view' => 'all', 'limit' => 10, 'descending' => 'true'
		]);
		$result = $couchdb->read($this->_query);
		$this->assertEqual(['total_rows' => 3, 'offset' => 0], $result->stats());

		$expected = [
			'id' => 'a1', 'rev' => '1-1', 'author' => 'author 1', 'body' => 'body 1'
		];
		$result = $result->data();
		$this->assertEqual($expected, $result['a1']);

		$expected = '/lithium-test/_design/latest/_view/all/';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = '?limit=10&descending=true';
		$result = $couchdb->last->request->queryString();
		$this->assertEqual($expected, $result);
	}

	public function testUpdate() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->_query->data(['id' => 12345, 'rev' => '1-1', 'title' => 'One']);

		$result = $couchdb->update($this->_query);
		$this->assertTrue($result);

		$expected = '/lithium-test/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = [];
		$result = $couchdb->last->request->query;
		$this->assertEqual($expected, $result);
	}

	public function testDelete() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->_query->data(['id' => 12345, 'rev' => '1-1', 'name' => 'Acme Inc']);

		$result = $couchdb->delete($this->_query);
		$this->assertTrue($result);

		$expected = '/lithium-test/12345';
		$result = $couchdb->last->request->path;
		$this->assertEqual($expected, $result);

		$expected = '?rev=1-1';
		$result = $couchdb->last->request->queryString();
		$this->assertEqual($expected, $result);
	}

	public function testEnabled() {
		$this->assertEqual(CouchDb::enabled(), true);

		$this->assertEqual(CouchDb::enabled('arrays'), true);
		$this->assertEqual(CouchDb::enabled('transactions'), false);
		$this->assertEqual(CouchDb::enabled('booleans'), true);
		$this->assertEqual(CouchDb::enabled('relationships'), false);
	}

	public function testRespondsTo() {
		$couchdb = new CouchDb($this->_testConfig);
		$this->assertTrue($couchdb->respondsTo('foobarbaz'));
		$this->assertFalse($couchdb->respondsTo(0));
	}

}

?>