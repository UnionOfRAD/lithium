<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\g11n\catalog\adapter;

use Exception;
use lithium\core\Libraries;
use lithium\g11n\catalog\adapter\Php;

class PhpTest extends \lithium\test\Unit {

	public $adapter;

	protected $_path;

	public function skip() {
		$this->_path = $path = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($path), "Path `{$path}` is not writable.");
	}

	public function setUp() {
		$this->adapter = new Php(['path' => $this->_path]);
	}

	public function tearDown() {
		$this->_cleanUp();
	}

	public function testPathMustExist() {
		try {
			new Php(['path' => $this->_path]);
			$result = true;
		} catch (Exception $e) {
			$result = false;
		}
		$this->assert($result);

		try {
			new Php(['path' => "{$this->_path}/i_do_not_exist"]);
			$result = false;
		} catch (Exception $e) {
			$result = true;
		}
		$this->assert($result);
	}

	public function testRead() {
		mkdir("{$this->_path}/fr/message", 0755, true);

		$data = <<<EOD
<?php
return [
	'politics' => 'politique',
	'house' => ['maison', 'maisons']
];
?>
EOD;
		file_put_contents("{$this->_path}/fr/message/default.php", $data);

		$result = $this->adapter->read('message', 'fr', null);
		$expected = [
			'politics' => [
				'id' => 'politics',
				'ids' => [],
				'translated' => 'politique',
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			],
			'house' => [
				'id' => 'house',
				'ids' => [],
				'translated' => ['maison', 'maisons'],
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$this->assertEqual($expected, $result);
	}

	public function testReadTemplate() {
		$data = <<<EOD
<?php
return [
	'politics' => 'politique',
	'house' => ['maison', 'maisons']
];
?>
EOD;
		file_put_contents("{$this->_path}/message_default.php", $data);

		$result = $this->adapter->read('messageTemplate', 'root', null);
		$expected = [
			'politics' => [
				'id' => 'politics',
				'ids' => [],
				'translated' => 'politique',
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			],
			'house' => [
				'id' => 'house',
				'ids' => [],
				'translated' => ['maison', 'maisons'],
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$this->assertEqual($expected, $result);
	}

	public function testReadWithScope() {
		mkdir("{$this->_path}/fr/message", 0755, true);

		$data = <<<EOD
<?php
return [
	'politics' => 'politique'
];
?>
EOD;
		file_put_contents("{$this->_path}/fr/message/li3_docs.php", $data);

		$result = $this->adapter->read('message', 'fr', null);
		$this->assertEmpty($result);

		$result = $this->adapter->read('message', 'fr', 'li3_docs');
		$expected = [
			'politics' => [
				'id' => 'politics',
				'ids' => [],
				'translated' => 'politique',
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$this->assertEqual($expected, $result);
	}

	public function testReadWithContext() {
		mkdir("{$this->_path}/fr/message", 0755, true);

		$data = <<<EOD
<?php
return [
	'green' => 'vert',
	'fast|speed' => 'rapide',
	'fast|go without food' => 'jeûner'
];
?>
EOD;
		file_put_contents("{$this->_path}/fr/message/default.php", $data);

		$result = $this->adapter->read('message', 'fr', null);
		$expected = [
			'green' => [
				'id' => 'green',
				'ids' => [],
				'translated' => 'vert',
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			],
			'fast|speed' => [
				'id' => 'fast',
				'ids' => [],
				'translated' => 'rapide',
				'flags' => [],
				'comments' => [],
				'occurrences' => [],
				'context' => 'speed'
			],
			'fast|go without food' => [
				'id' => 'fast',
				'ids' => [],
				'translated' => 'jeûner',
				'flags' => [],
				'comments' => [],
				'occurrences' => [],
				'context' => 'go without food'
			]
		];
		$this->assertEqual($expected, $result);
	}

	public function testReadValidation() {
		mkdir("{$this->_path}/fr/validation", 0755, true);

		$data = <<<EOD
<?php
return [
	'phone' => '/[0-9].*/i'
];
?>
EOD;
		file_put_contents("{$this->_path}/fr/validation/default.php", $data);

		$result = $this->adapter->read('validation', 'fr', null);
		$expected = [
			'phone' => [
				'id' => 'phone',
				'ids' => [],
				'translated' => '/[0-9].*/i',
				'flags' => [],
				'comments' => [],
				'occurrences' => []
			]
		];
		$this->assertEqual($expected, $result);

	}

	public function testReadWithAnonymousFunction() {
		mkdir("{$this->_path}/fr/message", 0755, true);

		$data = <<<EOD
<?php
return [
	'plural' => function() { return 123; },
	'politics' => 'politique',
];
?>
EOD;
		file_put_contents("{$this->_path}/fr/message/default.php", $data);

		$result = $this->adapter->read('message', 'fr', null);
		$expected = [
			'id' => 'politics',
			'ids' => [],
			'translated' => 'politique',
			'flags' => [],
			'comments' => [],
			'occurrences' => []
		];
		$this->assertEqual($expected, $result['politics']);

		$this->assertInternalType('callable', $result['plural']['translated']);

		$expected = 123;
		$result = $result['plural']['translated']();
		$this->assertEqual($expected, $result);
	}
}

?>