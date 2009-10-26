<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\storage\cache\adapters;

use \lithium\storage\cache\adapters\Memory;

class MemoryTest extends \lithium\test\Unit {

	public function setUp() {
		$this->Memory = new Memory();
	}

	public function tearDown() {
		unset($this->Memory);
	}

	public function testWriteAndRead() {
		$key = 'key';
		$data = 'data';

		$closure = $this->Memory->write($key, $data);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data');
		$result = $closure($this->Memory, $params, null);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$closure = $this->Memory->read($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memory, $params, null);
		$this->assertEqual($data, $result);
		$this->assertEqual($this->Memory->cache, array($key => $data));
	}

	public function testWriteAndDelete() {
		$key = 'key_to_delete';
		$data = 'some data to be deleted';

		$closure = $this->Memory->write($key, $data);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data');
		$result = $closure($this->Memory, $params, null);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$closure = $this->Memory->delete($key);
		$this->assertTrue(is_callable($closure));

		$params = compact('key');
		$result = $closure($this->Memory, $params, null);
		$this->assertTrue($result);

		$key = 'non_existent';
		$params = compact('key');
		$result = $closure($this->Memory, $params, null);
		$this->assertFalse($result);
	}

	public function testWriteAndClear() {
		$key = 'key_to_clear';
		$data = 'data to be cleared';

		$closure = $this->Memory->write($key, $data);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data');
		$result = $closure($this->Memory, $params, null);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$key2 = 'key2_to_clear';
		$data2 = 'data to be cleared';

		$closure = $this->Memory->write($key2, $data2);
		$this->assertTrue(is_callable($closure));

		$params = array('key' => $key2, 'data' => $data2);
		$result = $closure($this->Memory, $params, null);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->clear();
		$this->assertTrue($result);
		$this->assertEqual(array(), $this->Memory->cache);

		$closure = $this->Memory->write($key, $data);
		$this->assertTrue(is_callable($closure));

		$params = compact('key', 'data');
		$result = $closure($this->Memory, $params, null);
		$this->assertTrue($result);
		$this->assertEqual($this->Memory->cache, $result);

		$result = $this->Memory->clear();
		$this->assertTrue($result);
		$this->assertEqual(array(), $this->Memory->cache);

	}

	public function testClean() {
		$result = $this->Memory->clean();
		$this->assertFalse($result);
	}

}

?>