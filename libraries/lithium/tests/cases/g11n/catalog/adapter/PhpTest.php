<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n\catalog\adapter;

use \lithium\g11n\catalog\adapter\Php;

class PhpTest extends \lithium\test\Unit {

	public $adapter;

	public function skip() {
		$this->_path = $path = LITHIUM_APP_PATH . '/resources/tmp/tests';
		$message = "{$path} is not writable.";
		$this->skipIf(!is_writable($path), $message);
	}

	public function setUp() {
		mkdir($this->_path . '/fr/message', 0755, true);
		$this->adapter = new Php(array('path' => $this->_path));
	}

	public function tearDown() {
		$this->_cleanUp();
	}

	public function testRead() {
		$data = array(
			'politics' => 'politique',
			'house' => array('maison', 'maisons')
		);
		$contents[] = '<?php';
		$contents[] = 'return ' . var_export($data, true);
		$contents[] = '?>';
		file_put_contents($this->_path . '/fr/message/default.php', implode("\n", $contents));

		$result = $this->adapter->read('message', 'fr', null);
		$expected = array(
			'politics' => array(
				'id' => 'politics',
				'ids' => array(),
				'translated' => 'politique',
				'flags' => array(),
				'comments' => array(),
				'occurrences' => array()
			),
			'house' => array(
				'id' => 'house',
				'ids' => array(),
				'translated' => array('maison', 'maisons'),
				'flags' => array(),
				'comments' => array(),
				'occurrences' => array()
		));
		$this->assertEqual($expected, $result);
	}
}

?>