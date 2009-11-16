<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage;

use \lithium\storage\Session;
use \lithium\util\Collection;
use \lithium\storage\session\adapters\Memory;

class SessionStorageConditional extends Memory {

	public function read($key, $options = array()) {
		return isset($options['fail']) ? null : parent::read($key, $options);
	}

	public function write($key, $value, $options = array()) {
		return isset($options['fail']) ? null : parent::write($key, $value, $options);
	}
}

class SessionTest extends \lithium\test\Unit {

	public function setUp() {
		Session::config(array(
			'default' => array('adapter' => new Memory())
		));
	}

	public function testSessionInitialization() {
		$store1 = new Memory();
		$store2 = new Memory();
		$config = array(
			'store1' => array('adapter' => &$store1, 'filters' => array()),
			'store2' => array('adapter' => &$store2, 'filters' => array())
		);

		$result = Session::config($config);
		$this->assertEqual(new Collection(array('items' => $config)), $result);

		Session::clear();
		Session::config(array('store1' => array(
			'adapter' => 'lithium\storage\session\adapters\Memory',
			'filters' => array()
		)));
		$this->assertTrue(Session::write('key', 'value'));
		$result = Session::read('key');
		$expected = 'value';
		$this->assertEqual($expected, $result);
	}

	public function testSingleStoreReadWrite() {
		$this->assertNull(Session::read('key'));

		$this->assertTrue(Session::write('key', 'value'));
		$this->assertEqual(Session::read('key'), 'value');

		Session::clear();
		$this->assertNull(Session::read('key'));
		$this->assertIdentical(false, Session::write('key', 'value'));
	}

	public function testSessionConfigReset() {
		$this->assertTrue(Session::write('key', 'value'));
		$this->assertEqual(Session::read('key'), 'value');

		Session::clear();
		$this->assertFalse(Session::config()->count());

		$this->assertFalse(Session::read('key'));
		$this->assertFalse(Session::write('key', 'value'));
	}

	/**
	 * Tests a scenario where no session handler is available that matches the passed parameters.
	 *
	 * @return void
	 */
	public function testUnhandledWrite() {
		Session::config(array(
			'conditional' => array('adapter' => new SessionStorageConditional())
		));
		$result = Session::write('key', 'value', array('fail' => true));
		$this->assertFalse($result);
	}

	/**
	 * Tests deleting a session key from one or all adapters.
	 *
	 * @return void
	 */
	public function testSessionKeyCheckAndDelete() {
		Session::config(array(
			'temp' => array('adapter' => new Memory(), 'filters' => array()),
			'persistent' => array('adapter' => new Memory(), 'filters' => array())
		));
		Session::write('key1', 'value', array('name' => 'persistent'));
		Session::write('key2', 'value', array('name' => 'temp'));

		$result = Session::check('key1');
		$this->assertTrue($result);

		$result = Session::check('key2');
		$this->assertTrue($result);

		$result = Session::check('key1', array('name' => 'persistent'));
		$this->assertTrue($result);

		$result = Session::check('key1', array('name' => 'temp'));
		$this->assertFalse($result);

		$result = Session::check('key2', array('name' => 'persistent'));
		$this->assertFalse($result);

		$result = Session::check('key2', array('name' => 'temp'));
		$this->assertTrue($result);

		Session::delete('key1');
		$result = Session::check('key1');
		$this->assertFalse($result);

		Session::write('key1', 'value', array('name' => 'persistent'));
		$result = Session::check('key1');
		$this->assertTrue($result);

		Session::delete('key1', array('name' => 'temp'));
		$result = Session::check('key1');
		$this->assertTrue($result);

		Session::delete('key1', array('name' => 'persistent'));
		$result = Session::check('key1');
		$this->assertFalse($result);
	}

	/**
	 * Tests querying session keys from the primary adapter.  The memory adapter simply returns
	 * the HTTP request ID.
	 *
	 * @return void
	 */
	public function testSessionKey() {
		$this->skipIf(!isset($_SERVER['UNIQUE_ID']));

		$expected = $_SERVER['UNIQUE_ID'];
		$result = Session::key();
		$this->assertEqual($expected, $result);

		Session::clear();
		$this->assertNull(Session::key());
	}

	public function testConfigNoAdapters() {
		Session::config(array(
			'conditional' => array('adapter' => new SessionStorageConditional())
		));
		$this->assertTrue(Session::write('key', 'value'));
		$this->assertEqual(Session::read('key'), 'value');
		$this->assertFalse(Session::read('key', array('fail' => true)));
	}

	public function testSessionState() {
		$this->assertTrue(Session::isStarted());
		$this->assertTrue(Session::isStarted('default'));
		$this->assertFalse(Session::isStarted('invalid'));

		Session::clear();
		$this->assertFalse(Session::isStarted());
		$this->assertFalse(Session::isStarted('default'));
	}
}

?>