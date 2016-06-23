<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\model;

use lithium\data\entity\Document;
use lithium\data\collection\DocumentSet;

class MockDocumentPost extends \lithium\data\Model {

	protected $_meta = ['connection' => false, 'initialized' => true, 'key' => '_id'];

	public static function schema($field = null) {
		$schema = parent::schema();
		$schema->append([
			'_id' => ['type' => 'id'],
			'foo' => ['type' => 'object'],
			'foo.bar' => ['type' => 'int']
		]);
		return $schema;
	}

	public function ret($record, $param1 = null, $param2 = null) {
		if ($param2) {
			return $param2;
		}
		if ($param1) {
			return $param1;
		}
		return null;
	}

	public function medicin($record) {
		return 'lithium';
	}

	public static function find($type = 'all', array $options = []) {
		switch ($type) {
			case 'first':
				return new Document([
					'data' => ['_id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'],
					'model' => __CLASS__
				]);
			break;
			case 'all':
			default:
				return new DocumentSet([
					'data' => [
						['_id' => 1, 'name' => 'One', 'content' => 'Lorem ipsum one'],
						['_id' => 2, 'name' => 'Two', 'content' => 'Lorem ipsum two'],
						['_id' => 3, 'name' => 'Three', 'content' => 'Lorem ipsum three']
					],
					'model' => __CLASS__
				]);
			break;
		}
	}
}

?>