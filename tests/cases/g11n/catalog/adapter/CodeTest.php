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
use lithium\g11n\catalog\adapter\Code;

class CodeTest extends \lithium\test\Unit {

	public $adapter;

	protected $_path;

	public function setUp() {
		$this->_path = $path = Libraries::get(true, 'resources') . '/tmp/tests';
		$this->skipIf(!is_writable($this->_path), "Path `{$this->_path}` is not writable.");

		$this->adapter = new Code(compact('path'));

		$file = "{$this->_path}/a.php";
		$data = <<<'EOD'
<?php
$t('simple 1');

$t('options 1', null, ['locale' => 'en']);

$t('replace 1 {:a}', ['a' => 'b']);

$t('simple context', ['context' => 'foo']);
$t('simple context', ['context' => 'bar']);

$t('replace context 1 {:a}', ['a' => 'b', 'context' => 'foo']);
$t('replace context 1 {:a}', ['a' => 'b', 'context' => 'bar']);
$t('replace context 2 {:a}', ['context' => 'foo', 'a' => 'b']);
$t('replace context 2 {:a}', ['context' => 'bar', 'a' => 'b']);

$t($test['invalid']);
$t(32203);
$t('invalid 1', $test['invalid']);
$t('invalid 2', 32203);
$t('invalid 3', 'invalid 3b');

$t('escaping\n1');
$t("escaping\n2");
$t("escaping\r\n3");
$t('escaping
	4');

$tn('singular simple 1', 'plural simple 1', 3);
$tn('singular simple 2', 'plural simple 2');

$t('mixed 1');
$tn('mixed 1', 'plural mixed 1', 3);

$t('mixed 2');
$tn('mixed 2', 'plural mixed 2', 3);
$t('mixed 2');
$t('plural mixed 2');
?>
EOD;
		file_put_contents($file, $data);

		$file = "{$this->_path}/a.html.php";
		$data = <<<'EOD'
<?=$t('simple 1 short'); ?>

<?=$tn('singular simple 1 short', 'plural simple 1 short', 3); ?>
EOD;
		file_put_contents($file, $data);
	}

	public function tearDown() {
		$this->_cleanUp();
	}

	public function testPathMustExist() {
		$path = Libraries::get(true, 'resources') . '/tmp/tests';

		try {
			new Code(['path' => $this->_path]);
			$result = true;
		} catch (Exception $e) {
			$result = false;
		}
		$this->assert($result);

		try {
			new Code(['path' => "{$path}/i_do_not_exist"]);
			$result = false;
		} catch (Exception $e) {
			$result = true;
		}
		$this->assert($result);
	}

	public function testReadMessageTemplateTSimple() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$expected = ['singular' => 'simple 1'];
		$result = $results['simple 1']['ids'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTSimpleShort() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$expected = ['singular' => 'simple 1 short'];
		$result = $results['simple 1 short']['ids'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTOptions() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$expected = ['singular' => 'options 1'];
		$result = $results['options 1']['ids'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTReplace() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$expected = ['singular' => 'replace 1 {:a}'];
		$result = $results['replace 1 {:a}']['ids'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTContext() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$this->assertArrayHasKey('simple context|foo', $results);
		$this->assertArrayHasKey('simple context|bar', $results);

		$this->assertArrayHasKey('replace context 1 {:a}|foo', $results);
		$this->assertArrayHasKey('replace context 1 {:a}|bar', $results);

		$this->assertArrayHasKey('replace context 2 {:a}|foo', $results);
		$this->assertArrayHasKey('replace context 2 {:a}|bar', $results);

		$expected = ['ids' => ['singular' => 'simple context'], 'context' => 'foo'];
		$key = 'simple context|foo';
		$result = ['ids' => $results[$key]['ids'],	'context' => 'foo'];
		$this->assertEqual($expected, $result);

		$expected = ['ids' => ['singular' => 'simple context'], 'context' => 'bar'];
		$key = 'simple context|bar';
		$result = ['ids' => $results[$key]['ids'],	'context' => 'bar'];
		$this->assertEqual($expected, $result);

		$expected = ['ids' => ['singular' => 'replace context 1 {:a}'], 'context' => 'foo'];
		$key = 'replace context 1 {:a}|foo';
		$result = ['ids' => $results[$key]['ids'],	'context' => 'foo'];
		$this->assertEqual($expected, $result);

		$expected = ['ids' => ['singular' => 'replace context 1 {:a}'], 'context' => 'bar'];
		$key = 'replace context 1 {:a}|bar';
		$result = ['ids' => $results[$key]['ids'],	'context' => 'bar'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTInvalid() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$result = isset($results['32203']);
		$this->assertFalse($result);

		$result = isset($results[32203]);
		$this->assertFalse($result);

		$expected = ['singular' => 'invalid 1'];
		$result = $results['invalid 1']['ids'];
		$this->assertEqual($expected, $result);

		$expected = ['singular' => 'invalid 2'];
		$result = $results['invalid 2']['ids'];
		$this->assertEqual($expected, $result);

		$expected = ['singular' => 'invalid 3'];
		$result = $results['invalid 3']['ids'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTNoEscaping() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$expected = 'escaping\n1';
		$result = $results['escaping\n1']['ids']['singular'];
		$this->assertEqual($expected, $result);

		$expected = 'escaping\n2';
		$result = $results['escaping\n2']['ids']['singular'];
		$this->assertEqual($expected, $result);

		$expected = 'escaping\r\n3';
		$result = $results['escaping\r\n3']['ids']['singular'];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Due to different EOL handling on linux and windows, combined with
	 * the git config core.autocrlf setting, the test file may be written
	 * with either LF or CRLF as line endings, which has an effect on the
	 * key where the data is stored.
	 */
	public function testReadMessageTemplateTNoEscapingLineEnding() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$expectedLR = "escaping\n\t4";
		$expectedCRLF = "escaping\r\n\t4";

		$isSet = isset($results["escaping\n\t4"]['ids']['singular']);
		$assertLR = $isSet && $results["escaping\n\t4"]['ids']['singular'] === $expectedLR;

		$isSet = isset($results["escaping\r\n\t4"]['ids']['singular']);
		$assertCRLF = $isSet && $results["escaping\r\n\t4"]['ids']['singular'] === $expectedCRLF;
		$this->assertTrue($assertLR || $assertCRLF);
	}

	public function testReadMessageTemplateTnSimple() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$expected = 'singular simple 1';
		$result = $results['singular simple 1']['ids']['singular'];
		$this->assertEqual($expected, $result);

		$expected = 'plural simple 1';
		$result = $results['singular simple 1']['ids']['plural'];
		$this->assertEqual($expected, $result);

		$expected = 'singular simple 2';
		$result = $results['singular simple 2']['ids']['singular'];
		$this->assertEqual($expected, $result);

		$expected = 'plural simple 2';
		$result = $results['singular simple 2']['ids']['plural'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTnSimpleShort() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$expected = 'singular simple 1 short';
		$result = $results['singular simple 1 short']['ids']['singular'];
		$this->assertEqual($expected, $result);

		$expected = 'plural simple 1 short';
		$result = $results['singular simple 1 short']['ids']['plural'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTnT() {
		$results = $this->adapter->read('messageTemplate', 'root', null);

		$expected = 'mixed 1';
		$result = $results['mixed 1']['ids']['singular'];
		$this->assertEqual($expected, $result);

		$expected = 'plural mixed 1';
		$result = $results['mixed 1']['ids']['plural'];
		$this->assertEqual($expected, $result);

		$expected = 'mixed 2';
		$result = $results['mixed 2']['ids']['singular'];
		$this->assertEqual($expected, $result);

		$expected = 'plural mixed 2';
		$result = $results['mixed 2']['ids']['plural'];
		$this->assertEqual($expected, $result);
	}

	public function testReadWithScope() {
		$this->adapter = new Code(['path' => $this->_path, 'scope' => 'li3_bot']);

		$results = $this->adapter->read('messageTemplate', 'root', null);
		$this->assertEmpty($results);

		$results = $this->adapter->read('messageTemplate', 'root', 'li3_bot');
		$expected = ['singular' => 'simple 1'];
		$result = $results['simple 1']['ids'];
		$this->assertEqual($expected, $result);
	}

	public function testNoReadSupportForOtherCategories() {
		$result = $this->adapter->read('message', 'de', null);
		$this->assertEmpty($result);
	}
}

?>