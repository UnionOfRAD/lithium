<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 *
 */

namespace lithium\tests\cases\data\source;

use \lithium\data\source\MongoDb;

class MongoDbTest extends \lithium\test\Unit {

	protected $_testConfig = array(
		'type' => 'MongoDb',
		'database' => 'test',
		'host' => 'localhost',
		'port' => '27017',
		'persistent' => false
	);

	public function skip() {
		$message = 'MongoDb Extension is not loaded';
		$this->skipIf(!MongoDb::enabled(), $message);

		$mongodb = new MongoDb($this->_testConfig);
		$this->skipIf(
			!$mongodb->isConnected(),
			"`{$this->_testConfig['database']}` database or connection unavailable"
		);
	}



}

?>