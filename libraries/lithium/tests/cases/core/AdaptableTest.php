<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\core;

use \lithium\util\Collection;
use \lithium\core\Adaptable;
use \lithium\storage\cache\adapters\Memory;

class MockAdapter extends \lithium\core\Adaptable {

	protected static $_configurations = null;

	public static function adapter($name) {
		return static::_adapter('adapters.storage.cache', $name);
	}

}


class AdaptableTest extends \lithium\test\Unit {


	public function setUp() {
		$this->Adaptable = new Adaptable();
	}

	public function testConfig() {
		$Collection = new Collection();
		$this->assertEqual($Collection, $this->Adaptable->config());

		$items = array(array('adapter' => '\some\adapter', 'filters' => array('filter1', 'filter2')));
		$result = $this->Adaptable->config($items);
		$expected = new Collection(compact('items'));
		$this->assertEqual($expected, $result);
	}

	public function testReset() {
		$items = array(array('adapter' => '\some\adapter', 'filters' => array('filter1', 'filter2')));
		$result = $this->Adaptable->config($items);
		$expected = new Collection(compact('items'));
		$this->assertEqual($expected, $result);

		$result = $this->Adaptable->reset();
		$this->assertNull($result);

		$Collection = new Collection();
		$this->assertEqual($Collection, $this->Adaptable->config());
	}

	public function testAdapter() {
		$Adapter = new MockAdapter();

		$result = $Adapter::adapter('non_existent_config');
		$this->assertNull($result);

		$items = array('default' => array('adapter' => 'Memory', 'filters' => array()));
		$result = $Adapter::config($items);
		$expected = new Collection(compact('items'));
		$this->assertEqual($expected, $result);

		$result = $Adapter::adapter('default');
		$expected = new Memory();
		$this->assertEqual($expected, $result);

		// Will use last configured adapter
		$result = $Adapter::adapter(null);
		$expected = new Memory();
		$this->assertEqual($expected, $result);
	}

	public function testNonExistentAdapter() {
		$Adapter = new MockAdapter();

		$items = array('default' => array('adapter' => 'NonExistent', 'filters' => array()));
		$result = $Adapter::config($items);
		$expected = new Collection(compact('items'));
		$this->assertEqual($expected, $result);

		$result = $Adapter::adapter('default');
		$this->assertNull($result);
	}

}

?>