<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use \lithium\data\model\RecordSet;

class RecordDb extends \lithium\data\source\Database {

	protected $_records = array(
		'test1' => array(
		)
	);

	public function connect() {
	}

	public function disconnect() {
	}

	public function encoding($encoding = null) {
	}

	public function result($type, $resource, $context) {
	}

	public function columns($query, $resource = null, $context = null) {
		$cols = array(
			'test1' => array()
		);
		return $cols[$resource];
	}

	public function entities($_ = null) {
	}

	public function describe($entity, $meta = array()) {
	}
	
	public function error() {}
}

class RecordSetTest extends \lithium\test\Unit {

	protected $_recordSet = null;

	public function setUp() {
		$this->_recordSet = new RecordSet();
	}

	public function testColumnIntrospection() {
		
	}
}

?>
