<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data;


class MockProductForSchemas extends \lithium\data\Model {

	protected $_meta = array('source' => false, 'connection' => false);

	protected $_schema = array(
    'name' => array('type' => 'string', 'null' => false),
    'price' => array('type' => 'string', 'null' => false)
	);
}

?>