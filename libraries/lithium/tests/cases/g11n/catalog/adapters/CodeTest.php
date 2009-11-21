<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n\catalog\adapters;

use \lithium\g11n\catalog\adapters\Code;

if (false) {
	$t('simple 1');

	$t('options 1', null, array('locale' => 'en'));

	$t('replace 1 {:a}', array('a' => 'b'));

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
}

class CodeTest extends \lithium\test\Unit {

	public $adapter;

	public function setUp() {
		$path = __DIR__;
		$this->adapter = new Code(compact('path'));
	}

	public function testReadMessageTemplateTSimple() {
		$results = $this->adapter->read('message.template', 'root', null);

		$expected = 'simple 1';
		$result = $results['simple 1']['singularId'];
		$this->assertEqual($expected, $result);

		$result = $results['simple 1']['pluralId'];
		$this->assertFalse($result);
	}

	public function testReadMessageTemplateTOptions() {
		$results = $this->adapter->read('message.template', 'root', null);

		$expected = 'options 1';
		$result = $results['options 1']['singularId'];
		$this->assertEqual($expected, $result);

		$result = $results['options 1']['pluralId'];
		$this->assertFalse($result);
	}

	public function testReadMessageTemplateTReplace() {
		$results = $this->adapter->read('message.template', 'root', null);

		$expected = 'replace 1 {:a}';
		$result = $results['replace 1 {:a}']['singularId'];
		$this->assertEqual($expected, $result);

		$result = $results['replace 1 {:a}']['pluralId'];
		$this->assertFalse($result);
	}

	public function testReadMessageTemplateTInvalid() {
		$results = $this->adapter->read('message.template', 'root', null);

		$result = isset($results['32203']);
		$this->assertFalse($result);

		$result = isset($results[32203]);
		$this->assertFalse($result);

		$expected = 'invalid 1';
		$result = $results['invalid 1']['singularId'];
		$this->assertEqual($expected, $result);

		$result = $results['invalid 1']['pluralId'];
		$this->assertFalse($result);

		$expected = 'invalid 2';
		$result = $results['invalid 2']['singularId'];
		$this->assertEqual($expected, $result);

		$result = $results['invalid 2']['pluralId'];
		$this->assertFalse($result);

		$expected = 'invalid 3';
		$result = $results['invalid 3']['singularId'];
		$this->assertEqual($expected, $result);

		$result = $results['invalid 3']['pluralId'];
		$this->assertFalse($result);
	}

	public function testReadMessageTemplateTEscaping() {
		$results = $this->adapter->read('message.template', 'root', null);

		$expected = 'escaping\\\n1';
		$result = $results['escaping\\\n1']['singularId'];
		$this->assertEqual($expected, $result);

		$expected = 'escaping\n2';
		$result = $results['escaping\n2']['singularId'];
		$this->assertEqual($expected, $result);

		$expected = 'escaping\n3';
		$result = $results['escaping\n3']['singularId'];
		$this->assertEqual($expected, $result);

		$expected = 'escaping\n\t4';
		$result = $results['escaping\n\t4']['singularId'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTnSimple() {
		$results = $this->adapter->read('message.template', 'root', null);

		$expected = 'singular simple 1';
		$result = $results['singular simple 1']['singularId'];
		$this->assertEqual($expected, $result);

		$expected = 'plural simple 1';
		$result = $results['singular simple 1']['pluralId'];
		$this->assertEqual($expected, $result);

		$expected = 'singular simple 2';
		$result = $results['singular simple 2']['singularId'];
		$this->assertEqual($expected, $result);

		$expected = 'plural simple 2';
		$result = $results['singular simple 2']['pluralId'];
		$this->assertEqual($expected, $result);
	}

	public function testReadMessageTemplateTnT() {
		$results = $this->adapter->read('message.template', 'root', null);

		$expected = 'mixed 1';
		$result = $results['mixed 1']['singularId'];
		$this->assertEqual($expected, $result);

		$expected = 'plural mixed 1';
		$result = $results['mixed 1']['pluralId'];
		$this->assertEqual($expected, $result);

		$expected = 'mixed 2';
		$result = $results['mixed 2']['singularId'];
		$this->assertEqual($expected, $result);

		$expected = 'plural mixed 2';
		$result = $results['mixed 2']['pluralId'];
		$this->assertEqual($expected, $result);
	}
}

?>