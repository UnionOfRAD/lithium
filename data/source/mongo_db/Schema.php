<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\source\mongo_db;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Javascript;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Regex;
use MongoDB\BSON\Binary;

class Schema extends \lithium\data\DocumentSchema {

	protected $_handlers = [];

	protected $_types = [
		'MongoDB\BSON\ObjectId' => 'id',
		'MongoDB\BSON\UTCDateTime' => 'date',
		'MongoDB\BSON\Javascript' => 'code',
		'MongoDB\BSON\Binary' => 'binary',
		'MongoDB\BSON\Regex' => 'regex',
		'datetime' => 'date',
		'timestamp' => 'date',
		'int' => 'integer'
	];

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'fields'` _array_
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['fields' => ['_id' => ['type' => 'id']]];
		parent::__construct(array_filter($config) + $defaults);
	}

	protected function _init() {
		$this->_autoConfig[] = 'handlers';
		parent::_init();

		$this->_handlers += [
			'id' => function($v) {
				return is_string($v) && preg_match('/^[0-9a-f]{24}$/', $v) ? new ObjectId($v) : $v;
			},
			'date' => function($v) {
				$v = is_numeric($v) ? (integer) $v : strtotime($v);
				return !$v ? new UTCDateTime() : new UTCDateTime($v * 1000);
			},
			'regex' => function($v) { return new Regex($v); },
			'integer' => function($v) { return (integer) $v; },
			'float' => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; },
			'code' => function($v) { return new Javascript($v); },
			'binary' => function($v) { return new Binary($v); }
		];
	}
}

?>