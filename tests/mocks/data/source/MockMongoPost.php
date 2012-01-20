<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\mocks\data\source;

use lithium\data\source\MongoDb;

class MockMongoPost extends \lithium\tests\mocks\data\MockBase {

	protected $_meta = array('source' => 'posts', 'connection' => false);

	public static $connection;
}

?>