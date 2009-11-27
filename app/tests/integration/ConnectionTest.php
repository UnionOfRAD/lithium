<?php

namespace app\tests\integration;

use \lithium\data\Connections;

class ConnectionIntegration extends \lithium\test\Unit {

	public function testMySql() {
		$conn = Connections::get('default');
		$this->skipIf($conn === null ||
			!($conn instanceof \lithium\data\source\database\adapter\MySql));

		$this->assertTrue($conn->connect(), 'Unable to connect to MySQL');
	}

}

?>