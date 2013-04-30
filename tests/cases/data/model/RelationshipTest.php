<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use lithium\data\model\Relationship;

class RelationshipTest extends \lithium\test\Unit {

	protected $_gallery = 'lithium\tests\mocks\data\model\MockGallery';
	protected $_image = 'lithium\tests\mocks\data\model\MockImage';

	public function testRespondsTo() {
		$query = new Relationship(array(
			'type' => 'belongsTo',
			'fieldName' => 'bob',
			'to' => $this->_image
		));
		$this->assertTrue($query->respondsTo('foobarbaz'));
		$this->assertFalse($query->respondsTo(0));
	}

	public function testEmptyRequiredOptions() {
		$this->expectException("/`'type'`, `'fieldName'` and `'from'` options can't be empty./");
		$query = new Relationship();
	}

	public function testEmptyToAndName() {
		$this->expectException("/`'to'` and `'name'` options can't both be empty./");
		$query = new Relationship(array(
			'from' => $this->_gallery,
			'type' => 'belongsTo',
			'fieldName' => 'field_id'
		));
	}
}

?>