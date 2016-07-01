<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\fixture\model\gallery;

class TagsFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\gallery\Tags';

	protected $_fields = [
		'id' => ['type' => 'id'],
		'name' => ['type' => 'string', 'length' => 50],
		'author_id' => ['type' => 'integer', 'length' => 11]
	];

	protected $_records = [
		['id' => 1, 'name' => 'High Tech', 'author_id' => 6],
		['id' => 2, 'name' => 'Sport', 'author_id' => 9],
		['id' => 3, 'name' => 'Computer', 'author_id' => 6],
		['id' => 4, 'name' => 'Art', 'author_id' => 2],
		['id' => 5, 'name' => 'Science', 'author_id' => 1],
		['id' => 6, 'name' => 'City', 'author_id' => 2]
	];
}

?>