<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2014, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\source\database\adapter;

use lithium\data\Connections;
use lithium\tests\mocks\data\model\database\adapter\MockPostgreSql;
use lithium\tests\mocks\data\model\MockDatabasePost;
use lithium\tests\mocks\data\model\database\MockResult;

class PostgreSqlTest extends \lithium\test\Unit {

	protected $_db = null;

	public function setUp() {
		Connections::add('mock', array(
			'object' => $this->_db = new MockPostgreSql()
		));
		MockDatabasePost::config(array(
			'meta' => array('connection' => 'mock')
		));
	}

	public function tearDown() {
		Connections::remove('mock');
		MockDatabasePost::reset();
	}

	public function testHasManyRelationWithLimitAndOrder() {
		$this->_db->log = true;
		$this->_db->return['_execute'] = new MockResult(array(
			'records' => array(
				array(0 => 5)
			)
		));

		MockDatabasePost::first(array(
			'with' => array(
				'MockDatabaseComment',
			),
			'order' => array(
				'title',
				'MockDatabaseComment.body' => 'DESC'
			)
		));
		$this->_db->log = false;

		$result = $this->_db->logs;

		$expected[0] = <<<SQL
SELECT _ID_ FROM (
		SELECT DISTINCT ON({MockDatabasePost}.{id}) {MockDatabasePost}.{id} AS _ID_,
			{MockDatabasePost}.{title} AS {_MockDatabasePost_title_},
			{MockDatabaseComment}.{body} AS {_MockDatabaseComment_body_}
			FROM {mock_database_posts} AS {MockDatabasePost}
			LEFT JOIN {mock_database_comments} AS {MockDatabaseComment}
				ON {MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id}
	) AS _TEMP_
	ORDER BY {_MockDatabasePost_title_} ASC, {_MockDatabaseComment_body_} DESC
	LIMIT 1;
SQL;
		$expected[1] = <<<SQL
SELECT * FROM {mock_database_posts} AS {MockDatabasePost}
	LEFT JOIN {mock_database_comments} AS {MockDatabaseComment}
		ON {MockDatabasePost}.{id} = {MockDatabaseComment}.{mock_database_post_id}
	WHERE {MockDatabasePost}.{id} IN (5)
	ORDER BY {MockDatabasePost}.{title} ASC, {MockDatabaseComment}.{body} DESC;
SQL;

		$expected = array_map(function($v) {
			return preg_replace('/[\t\n]+/', ' ', $v);
		}, $expected);
		$this->assertEqual($expected, $result);
	}
}

?>