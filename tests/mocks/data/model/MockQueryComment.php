<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\data\model;

class MockQueryComment extends \lithium\data\Model {

	public $belongsTo = ['MockQueryPost'];

	protected $_meta = ['connection' => false];

	protected $_schema = [
		'id' => ['type' => 'integer', 'key' => 'primary'],
		'author_id' => ['type' => 'integer'],
		'comment' => ['type' => 'text'],
		'created' => ['type' => 'datetime'],
		'updated' => ['type' => 'datetime']
	];
}

?>