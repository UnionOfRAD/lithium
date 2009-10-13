<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\g11n\catalog\adapters;

use \lithium\g11n\catalog\adapters\Code;

if (false) {
	$t('message 1');
	$t('message 2', array('a' => 'b'));

	$t($test['invalid']);
	$t(32203);
	$t('message 3', $test['invalid']);
	$t('message 4', 32203);

	$t('message\n5');
	$t("message\n6");
	$t("message\r\n7");
	$t('message
	8');

	$t('singular 1', 'plural 1');
	$t('singular 2', 'plural 2', array('a' => 'b'));

	$t('mixed 1');
	$t('mixed 1', 'plural 3');
}

class CodeTest extends \lithium\test\Unit {

	public $adapter;

	public function setUp() {
		$path = __DIR__;
		$this->adapter = new Code(compact('path'));
	}

	/**
	 * Tests message string parsing, invalid values must be skipped.
	 *
	 * @return void
	 */
	public function testReadMessageTemplate() {
		$result = $this->adapter->read('message.template', 'root', null);

		/* Simple */

		$this->assertEqual('message 1', $result['message 1']['singularId']);
		$this->assertFalse($result['message 1']['pluralId']);

		$this->assertEqual('message 2', $result['message 2']['singularId']);
		$this->assertFalse($result['message 2']['pluralId']);

		$this->assertFalse(isset($result['32203']));
		$this->assertFalse(isset($result[32203]));

		$this->assertEqual('message 3', $result['message 3']['singularId']);
		$this->assertFalse($result['message 3']['pluralId']);

		$this->assertEqual('message 4', $result['message 4']['singularId']);
		$this->assertFalse($result['message 4']['pluralId']);

		/* Escaping */

		$this->assertEqual('message\\\n5', $result['message\\\n5']['singularId']);
		$this->assertEqual('message\n6', $result['message\n6']['singularId']);
		$this->assertEqual('message\n7', $result['message\n7']['singularId']);
		$this->assertEqual('message\n\t8', $result['message\n\t8']['singularId']);

		/* Plurals */

		$this->assertEqual('singular 1', $result['singular 1']['singularId']);
		$this->assertEqual('plural 1', $result['singular 1']['pluralId']);

		$this->assertEqual('singular 2', $result['singular 2']['singularId']);
		$this->assertEqual('plural 2', $result['singular 2']['pluralId']);

		/* Merging simple and plural message strings */

		$this->assertEqual('mixed 1', $result['mixed 1']['singularId'], 'mixed 1');
		$this->assertEqual('plural 3', $result['mixed 1']['pluralId'], 'plural 3');
	}
}

?>