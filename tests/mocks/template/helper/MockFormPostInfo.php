<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\template\helper;

class MockFormPostInfo extends \lithium\data\Model {

	protected $_schema = [
		'id' => ['type' => 'integer'],
		'section' => ['type' => 'string'],
		'notes' => ['type' => 'text'],
		'created' => ['type' => 'datetime'],
		'updated' => ['type' => 'datetime']
	];
}

?>