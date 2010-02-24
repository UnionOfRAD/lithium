<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n\catalog\adapter;

use \Exception;
use \lithium\g11n\catalog\adapter\Php;

class PhpTest extends \lithium\test\Unit {

	public $adapter;

	protected $_path;

	public function skip() {
		$this->_path = $path = LITHIUM_APP_PATH . '/resources/tmp/tests';
		$message = "{$path} is not writable.";
		$this->skipIf(!is_writable($path), $message);
	}

	public function setUp() {
		$this->adapter = new Php(array('path' => $this->_path));
	}

	public function tearDown() {
		$this->_cleanUp();
	}

	public function testPathMustExist() {
		try {
			new Php(array('path' => $this->_path));
			$result = true;
		} catch (Exception $e) {
			$result = false;
		}
		$this->assert($result);

		try {
			new Php(array('path' => "{$this->_path}/i_do_not_exist"));
			$result = false;
		} catch (Exception $e) {
			$result = true;
		}
		$this->assert($result);
	}

	public function testRead() {
		mkdir("{$this->_path}/fr/message", 0755, true);

		$data = array(
			'politics' => 'politique',
			'house' => array('maison', 'maisons')
		);
		$contents[] = '<?php';
		$contents[] = 'return ' . var_export($data, true);
		$contents[] = '?>';
		file_put_contents("{$this->_path}/fr/message/default.php", implode("\n", $contents));

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

	public function testReadTemplate() {
		$data = array(
			'politics' => 'politique',
			'house' => array('maison', 'maisons')
		);
		$contents[] = '<?php';
		$contents[] = 'return ' . var_export($data, true);
		$contents[] = '?>';
		file_put_contents("{$this->_path}/message_default.php", implode("\n", $contents));

		$result = $this->adapter->read('messageTemplate', 'root', null);
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

	public function testReadWithScope() {
		mkdir("{$this->_path}/fr/message", 0755, true);

		$data = array(
			'politics' => 'politique'
		);
		$contents[] = '<?php';
		$contents[] = 'return ' . var_export($data, true);
		$contents[] = '?>';
		file_put_contents("{$this->_path}/fr/message/li3_docs.php", implode("\n", $contents));

		$result = $this->adapter->read('message', 'fr', null);
		$this->assertFalse($result);

		$result = $this->adapter->read('message', 'fr', 'li3_docs');
		$expected = array(
			'politics' => array(
				'id' => 'politics',
				'ids' => array(),
				'translated' => 'politique',
				'flags' => array(),
				'comments' => array(),
				'occurrences' => array()
		));
		$this->assertEqual($expected, $result);
	}
}

?>