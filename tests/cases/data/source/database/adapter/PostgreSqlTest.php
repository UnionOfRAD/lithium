<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\data\source\database\adapter;

use lithium\data\Connections;
use lithium\tests\mocks\data\model\database\adapter\MockPostgreSql;
use lithium\tests\mocks\data\model\MockDatabasePost;
use lithium\tests\mocks\data\model\database\MockResult;

class PostgreSqlTest extends \lithium\test\Unit {

	protected $_db = null;

	public function setUp() {
		Connections::add('mock', [
			'object' => $this->_db = new MockPostgreSql()
		]);
		MockDatabasePost::config([
			'meta' => ['connection' => 'mock']
		]);
	}

	public function tearDown() {
		Connections::remove('mock');
		MockDatabasePost::reset();
	}

	public function testHasManyRelationWithLimitAndOrder() {
		$this->_db->log = true;
		$this->_db->return['_execute'] = new MockResult([
			'records' => [
				[0 => 5]
			]
		]);

		MockDatabasePost::first([
			'with' => [
				'MockDatabaseComment',
			],
			'order' => [
				'title',
				'id',
				'MockDatabaseComment.body' => 'DESC'
			]
		]);
		$this->_db->log = false;

		$result = $this->_db->logs;

		$expected[0] = <<<SQL
SELECT _ID_ FROM (
		SELECT DISTINCT ON({MockDatabasePost}.{id}) {MockDatabasePost}.{id} AS _ID_,
			{MockDatabasePost}.{title} AS {_MockDatabasePost_title_},
			{MockDatabasePost}.{id} AS {_MockDatabasePost_id_},
			{MockDatabaseComment}.{body} AS {_MockDatabaseComment_body_}
			FROM {mock_database_posts} AS {MockDatabasePost}
			LEFT JOIN {mock_database_comments} AS {MockDatabaseComment}
				ON {MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id}
	) AS _TEMP_
	ORDER BY {_MockDatabasePost_title_} ASC, {_MockDatabasePost_id_} ASC, {_MockDatabaseComment_body_} DESC
	LIMIT 1;
SQL;
		$expected[1] = <<<SQL
SELECT * FROM {mock_database_posts} AS {MockDatabasePost}
	LEFT JOIN {mock_database_comments} AS {MockDatabaseComment}
		ON {MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id}
	WHERE {MockDatabasePost}.{id} IN (5)
	ORDER BY {MockDatabasePost}.{title} ASC, {MockDatabasePost}.{id} ASC, {MockDatabaseComment}.{body} DESC;
SQL;

		$expected = array_map(function($v) {
			return preg_replace('/[\t\n]+/', ' ', $v);
		}, $expected);
		$this->assertEqual($expected, $result);
	}

	public function testDsnWithHostPort() {
		$db = new MockPostgreSql([
			'autoConnect' => false,
			'database' => 'test',
			'host' => 'localhost:1234',
		]);
		$expected = 'pgsql:host=localhost;port=1234;dbname=test';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);
	}

	public function testDsnWithHost() {
		$db = new MockPostgreSql([
			'autoConnect' => false,
			'database' => 'test',
			'host' => 'localhost',
		]);
		$expected = 'pgsql:host=localhost;port=5432;dbname=test';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);
	}

	public function testDsnWithPort() {
		$db = new MockPostgreSql([
			'autoConnect' => false,
			'database' => 'test',
			'host' => ':1234',
		]);
		$expected = 'pgsql:host=localhost;port=1234;dbname=test';
		$result = $db->dsn();
		$this->assertEqual($expected, $result);
	}
}

?>