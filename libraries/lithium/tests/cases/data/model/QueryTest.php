<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use \lithium\data\model\Query;
use \lithium\tests\mocks\data\model\MockQueryPost;
use \lithium\tests\mocks\data\model\MockQueryComment;

class QueryTest extends \lithium\test\Unit {

	public function setUp() {
		MockQueryPost::init();
		MockQueryComment::init();
	}

	/**
	 * Tests that configuration settings are delegating to matching method names
	 *
	 * @return void
	 */
	public function testObjectConstruction() {
		$query = new Query();
		$this->assertFalse($query->conditions());

		$query = new Query(array('conditions' => 'foo', 'fields' => array('id')));
		$this->assertEqual($query->conditions(), array('foo'));
	}

	public function testQueryExport() {
		$query = new Query();
	}
}

?>