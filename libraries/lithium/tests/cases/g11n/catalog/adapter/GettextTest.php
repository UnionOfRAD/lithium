<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n\catalog\adapter;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \lithium\g11n\catalog\adapter\Gettext;

class GettextTest extends \lithium\test\Unit {

	public $adapter;

	public function skip() {
		$path = LITHIUM_APP_PATH . '/resources/tmp/tests';
		$message = "Path {$path} is not writable.";
		$this->skipIf(!is_writable($path), $message);
	}

	public function setUp() {
		$this->_path = $path = LITHIUM_APP_PATH . '/resources/tmp/tests/g11n';
		mkdir($this->_path . '/en/LC_MESSAGES', 0755, true);
		mkdir($this->_path . '/de/LC_MESSAGES', 0755, true);
		$this->adapter = new Gettext(compact('path'));
	}

	public function tearDown() {
		$this->_cleanUp();
	}

	public function testWriteReadMessageTemplate() {
		$data = array(
			'singular 1' => array(
				'id' => 'singular 1',
				'ids' => array('singular' => 'singular 1', 'plural' => 'plural 1'),
				'flags' => array('fuzzy' => true),
				'translated' => array(),
				'occurrences' => array(
					array('file' => 'test.php', 'line' => 1)
				),
				'comments' => array(
					'comment 1'
				),
			)
		);
		$meta = array();

		$this->adapter->write('messageTemplate', 'root', null, $data);
		$this->assertTrue(file_exists($this->_path . '/message_default.pot'));

		$result = $this->adapter->read('messageTemplate', 'root', null);
		$this->assertEqual($data, $result);
	}
}

?>