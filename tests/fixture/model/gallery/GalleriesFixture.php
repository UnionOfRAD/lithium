<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\fixture\model\gallery;

class GalleriesFixture extends \li3_fixtures\test\Fixture {

	protected $_model = 'lithium\tests\fixture\model\gallery\Galleries';

	protected $_fields = [
		'id' => ['type' => 'id'],
		'name' => ['type' => 'string', 'length' => 50],
		'active' => ['type' => 'boolean', 'default' => true],
		'created' => ['type' => 'datetime'],
		'modified' => ['type' => 'datetime']
	];

	protected $_records = [
		[
			'name' => 'Foo Gallery',
			'active' => true,
			'created' => '2007-06-20 21:02:27',
			'modified' => '2009-12-14 22:36:09'
		],
		[
			'name' => 'Bar Gallery',
			'active' => true,
			'created' => '2008-08-22 16:12:42',
			'modified' => '2008-08-22 16:12:42'
		],
	];
}

?>