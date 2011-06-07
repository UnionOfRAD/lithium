<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\data;

use Exception;
use lithium\data\Connections;
use lithium\data\source\Http;
use lithium\data\source\Mock;
use lithium\data\source\database\adapter\MySql;

class ConnectionsTest extends \lithium\test\Unit {

	public $config = array(
		'type'     => 'database',
		'adapter'  => 'MySql',
		'host'     => 'localhost',
		'login'    => '--user--',
		'password' => '--pass--',
		'database' => 'db'
	);

	protected $_preserved = array();

	public function setUp() {
		if (empty($this->_preserved)) {
			foreach (Connections::get() as $conn) {
				$this->_preserved[$conn] = Connections::get($conn, array('config' => true));
			}
		}
		Connections::reset();
	}

	public function tearDown() {
		foreach ($this->_preserved as $name => $config) {
			Connections::add($name, $config);
		}
	}

	public function testConnectionCreate() {
		$result = Connections::add('conn-test', array('type' => 'database') + $this->config);
		$expected = $this->config + array('type' => 'database');
		$this->assertEqual($expected, $result);

		$this->skipIf(!MySql::enabled(), 'MySql is not enabled');
		$this->skipIf(!$this->_canConnect('localhost', 3306), 'Cannot connect to localhost:3306');

		$this->expectException('/mysql_get_server_info/');
		$this->expectException('/mysql_select_db/');
		$this->expectException('/mysql_pconnect/');
		$result = Connections::get('conn-test');
		$this->assertTrue($result instanceof MySql);

		$result = Connections::add('conn-test-2', $this->config);
		$this->assertEqual($expected, $result);

		$this->expectException('/mysql_get_server_info/');
		$this->expectException('/mysql_select_db/');
		$this->expectException('/mysql_pconnect/');
		$result = Connections::get('conn-test-2');
		$this->assertTrue($result instanceof MySql);
	}

	public function testConnectionGetAndReset() {
		Connections::add('conn-test', $this->config);
		Connections::add('conn-test-2', $this->config);
		$this->assertEqual(array('conn-test', 'conn-test-2'), Connections::get());

		$this->skipIf(!MySql::enabled(), 'MySql is not enabled');
		$this->skipIf(!$this->_canConnect('localhost', 3306), 'Cannot connect to localhost:3306');

		$expected = $this->config + array('type' => 'database', 'filters' => array());
		$this->assertEqual($expected, Connections::get('conn-test', array('config' => true)));

		$this->assertNull(Connections::reset());
		$this->assertFalse(Connections::get());

		$this->assertTrue(is_array(Connections::get()));
	}

	public function testConnectionAutoInstantiation() {
		Connections::add('conn-test', $this->config);
		Connections::add('conn-test-2', $this->config);

		$this->skipIf(!MySql::enabled(), 'MySql is not enabled');
		$this->skipIf(!$this->_canConnect('localhost', 3306), 'Cannot connect to localhost:3306');

		$this->expectException('/mysql_get_server_info/');
		$this->expectException('/mysql_select_db/');
		$this->expectException('/mysql_pconnect/');
		$result = Connections::get('conn-test');
		$this->assertTrue($result instanceof MySql);

		$result = Connections::get('conn-test');
		$this->assertTrue($result instanceof MySql);

		$this->assertNull(Connections::get('conn-test-2', array('autoCreate' => false)));
	}

	public function testInvalidConnection() {
		$this->assertNull(Connections::get('conn-invalid'));
	}

	public function testStreamConnection() {
		$config = array(
			'type' => 'Http',
			'socket' => 'Stream',
			'host' => 'localhost',
			'login' => 'root',
			'password' => '',
			'port' => '80'
		);

		Connections::add('stream-test', $config);
		$result = Connections::get('stream-test');
		$this->assertTrue($result instanceof Http);
		Connections::config(array('stream-test' => false));
	}

	public function testErrorExceptions() {
		$config = array(
			'adapter' => 'None',
			'type' => 'Error'
		);
		Connections::add('NoConnection', $config);
		$result = false;

		try {
			Connections::get('NoConnection');
		} catch(Exception $e) {
			$result = true;
		}
		$this->assertTrue($result, 'Exception is not thrown');
	}

	public function testGetNullAdapter() {
		Connections::reset();
		$this->assertTrue(Connections::get(false) instanceof Mock);
	}

	protected function _canConnect($host, $port) {
		$this->expectException();
		$this->expectException();

		if ($conn = fsockopen($host, $port)) {
			array_pop($this->_expected);
			array_pop($this->_expected);
			fclose($conn);

			return true;
		}
		return false;
	}
}

?>