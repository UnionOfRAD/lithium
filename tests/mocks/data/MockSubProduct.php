<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;

class MockSubProduct extends \lithium\tests\mocks\data\MockProduct {

	protected $_meta = array('source' => 'mock_products', 'connection' => false);

	protected $_custom = array(
		'prop2' => 'value2'
	);

	protected $_schema = array(
		'refurb' => array('type' => 'boolean')
	);

	public $validates = array(
		'refurb' => array(
			array(
				'boolean',
				'message' => 'Must have a boolean value.'
			)
		)
	);
}

?>