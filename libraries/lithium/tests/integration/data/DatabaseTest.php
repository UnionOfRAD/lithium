<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\data;

use lithium\data\Connections;
use lithium\data\model\Query;
use lithium\data\source\Database;

class DatabaseTest extends \lithium\test\Unit {

	public $db = null;

	public function skip() {
		$isAvailable = (
			Connections::get('test', array('config' => true)) &&
			Connections::get('test')->isConnected(array('autoConnect' => true))
		);
		$this->skipIf(!$isAvailable, "No test connection available.");

		$isDatabase = Connections::get('test') instanceof Database;
		$this->skipIf(!$isAvailable, "The 'test' connection is not a relational database.");
	}

	public function setUp() {
		$this->db = Connections::get('test');
	}

	public function testQueryManyToOne() {
		$query = new Query(array(
			'type' => 'read',
			'model' => 'lithium\tests\mocks\data\source\Images',
			'source' => 'images',
			'alias' => 'Images',
			'joins' => array(new Query(array(
				'type' => 'LEFT',
				'model' => 'lithium\tests\mocks\data\source\Galleries',
				'source' => 'galleries',
				'alias' => 'Gallery',
				'constraint' => array('Gallery.id' => 'Images.gallery_id')
			)))
		));
		$images = $this->db->read($query);
	}
}

?>