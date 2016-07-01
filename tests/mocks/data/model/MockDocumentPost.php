<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
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