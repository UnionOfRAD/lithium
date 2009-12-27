<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n\catalog\adapter;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \lithium\g11n\catalog\adapter\Gettext;

class GettextTest extends \lithium\test\Unit {

	public $adapter;

	public function setUp() {
		$this->_path = $path = LITHIUM_APP_PATH . '/resources/tmp/tests/g11n';
		mkdir($this->_path . '/en/LC_MESSAGES', 0755, true);
		mkdir($this->_path . '/de/LC_MESSAGES', 0755, true);
		$this->adapter = new Gettext(compact('path'));
	}

	public function tearDown() {
		$base = new RecursiveDirectoryIterator($this->_path);
		$iterator = new RecursiveIteratorIterator($base, RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($iterator as $item) {
			$path = $item->getPathname();

			if ($item->isDir()) {
				rmdir($path);
			} else {
				unlink($path);
			}
		}
		rmdir($this->_path);
	}

	public function testWriteReadMessageTemplate() {
		$data = array(
			'singular 1' => array(
				'singularId' => 'singular 1',
				'pluralId' => 'plural 1',
				'translated' => array(),
				'occurrences' => array(
					array('file' => 'test.php', 'line' => 1)
				),
				'comments' => array(
					'comment 1'
				),
				'fuzzy' => true,
			)
		);
		$meta = array();

		$this->adapter->write('message.template', 'root', null, $data);
		$this->assertTrue(file_exists($this->_path . '/message_default.pot'));

		$result = $this->adapter->read('message.template', 'root', null);
		$this->assertEqual($data, $result);
	}
}

?>