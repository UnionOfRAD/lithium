<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\fixture\model\mongodb;

class TagsFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\mongodb\Tags';

	protected $_fields = [
		'_id' => ['type' => 'id'],
		'name' => ['type' => 'string', 'length' => 50],
		'author' => ['type' => 'id']
	];

	protected $_records = [
		['_id' => 1, 'name' => 'High Tech', 'author' => 6],
		['_id' => 2, 'name' => 'Sport', 'author' => 9],
		['_id' => 3, 'name' => 'Computer', 'author' => 6],
		['_id' => 4, 'name' => 'Art', 'author' => 2],
		['_id' => 5, 'name' => 'Science', 'author' => 1],
		['_id' => 6, 'name' => 'City', 'author' => 2]
	];
}

?>