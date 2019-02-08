<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\source;

use lithium\data\source\mongo_db\Schema;

class MockMongoPost extends \lithium\data\Model {

	protected $_meta = ['source' => 'posts', 'connection' => false, 'key' => '_id'];

	public static $connection;

	public static function schema($field = null) {
		$result = parent::schema($field);

		if (is_object($result) && get_class($result) === 'lithium\data\Schema') {
			return new Schema(['fields' => $result->fields(), 'meta'   => $result->meta()]);
		}
		return $result;
	}
}

?>