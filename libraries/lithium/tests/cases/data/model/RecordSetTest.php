<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data\model;

use \lithium\data\model\RecordSet;

class RecordSetTest extends \lithium\test\Unit {

	protected $_recordSet = null;

	public function setUp() {
		$this->_recordSet = new RecordSet();
	}

	public function testColumnIntrospection() {

	}
}

?>