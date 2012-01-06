<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Tests\Mocks\Data;

class MockCreator extends \Lithium\Data\Model {

	protected $_meta = array('connection' => 'mock-source');

	protected $_schema = array(
		'name' => array(
			'default' => 'Moe',
			'type' => 'string',
			'null' => false
		),
		'sign' => array(
			'default' => 'bar',
			'type' => 'string',
			'null' => false
		),
		'age' => array(
			'default' => 0,
			'type' => 'number',
			'null' => false
		)
	);
}

?>