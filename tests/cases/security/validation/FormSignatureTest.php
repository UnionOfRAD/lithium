<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2014, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\security\validation;

use lithium\action\Request;
use lithium\security\validation\FormSignature;

class FormSignatureTest extends \lithium\test\Unit {

	/**
	 * Tests that `FormSignature` fails to generate a matching signature for data where locked
	 * values have been tampered with.
	 */
	public function testFailLockedFieldValueChange() {
		$signature = FormSignature::key(array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever'
			),
			'locked' => array(
				'active' => 'true'
			)
		));
		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'active' => 'false',
			'security' => compact('signature')
		)));
		$this->assertFalse(FormSignature::check($request));
	}

	/**
	 * Tests that `FormSignature` correctly ignores other fields in the `'security'` array when
	 * generating signatures.
	 */
	public function testIgnoreSecurityFields() {
		$signature = FormSignature::key(array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever'
			),
			'locked' => array(
				'active' => 'true'
			)
		));
		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'active' => 'true',
			'security' => compact('signature') + array('foo' => 'bar')
		)));
		$this->assertTrue(FormSignature::check($request));
	}

	public function testFailsWithAddedFieldAndLocked() {
		$data = array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever',
			),
			'locked' => array(
				'active' => 'true'
			)
		);
		$signatures[] = FormSignature::key($data);

		$data['fields']['invalidField'] = 'foo';
		$signatures[] = FormSignature::key($data);

		$this->assertNotEqual($signatures[0], $signatures[1]);
	}

	public function testFailsTamperedFieldsWithMany() {
		for ($original = array(), $i = 0; $i < 100; $i++) {
			$original['foo' . $i] = 'bar' . $i;
		}
		$signature0 = FormSignature::key(array(
			'fields' => $original
		));

		$changed = $original;
		$changed['foo10000'] = 'barAdded';
		$signature1 = FormSignature::key(array(
			'fields' => $changed
		));
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $original;
		unset($changed['foo1']);
		$signature1 = FormSignature::key(array(
			'fields' => $changed
		));
		$this->assertNotIdentical($signature0, $signature1);
	}

	public function testFailsTamperedLockedWithMany() {
		for ($original = array(), $i = 0; $i < 100; $i++) {
			$original['foo' . $i] = 'bar' . $i;
		}
		$signature0 = FormSignature::key(array(
			'locked' => $original
		));

		$changed = $original;
		$changed['foo90'] = 'barChanged';
		$signature1 = FormSignature::key(array(
			'locked' => $changed
		));
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $original;
		$changed['foo10000'] = 'barAdded';
		$signature1 = FormSignature::key(array(
			'locked' => $changed
		));
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $original;
		unset($changed['foo1']);
		$signature1 = FormSignature::key(array(
			'locked' => $changed
		));
		$this->assertNotIdentical($signature0, $signature1);
	}

	public function testFailsTamperedFieldsAndLockedWithManyAndLockedChange() {
		for ($originalFields = array(), $i = 0; $i < 20; $i++) {
			$originalFields['fooa' . $i] = 'bara' . $i;
		}
		for ($originalLocked = array(), $i = 0; $i < 20; $i++) {
			$originalLocked['foob' . $i] = 'barb' . $i;
		}
		$signature0 = FormSignature::key(array(
			'fields' => $originalFields,
			'locked' => $originalLocked
		));

		$changed = $originalLocked;
		$changed['foo90'] = 'barChanged';
		$signature1 = FormSignature::key(array(
			'fields' => $originalFields,
			'locked' => $changed
		));
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $originalLocked;
		$changed['foo10000'] = 'barAdded';
		$signature1 = FormSignature::key(array(
			'fields' => $originalFields,
			'locked' => $changed
		));
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $originalLocked;
		unset($changed['foo1']);
		$signature1 = FormSignature::key(array(
			'fields' => $originalFields,
			'locked' => $changed
		));
		$this->assertNotIdentical($signature0, $signature1);
	}

	public function testFailsTamperedFieldsAndLockedWithManyAndFieldsChange() {
		for ($originalFields = array(), $i = 0; $i < 20; $i++) {
			$originalFields['fooa' . $i] = 'bara' . $i;
		}
		for ($originalLocked = array(), $i = 0; $i < 20; $i++) {
			$originalLocked['foob' . $i] = 'barb' . $i;
		}
		$signature0 = FormSignature::key(array(
			'fields' => $originalFields,
			'locked' => $originalLocked
		));

		$changed = $originalFields;
		$changed['foo10000'] = 'barAdded';
		$signature1 = FormSignature::key(array(
			'fields' => $changed,
			'locked' => $originalLocked
		));
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $originalFields;
		unset($changed['foo1a']);
		$signature1 = FormSignature::key(array(
			'fields' => $changed,
			'locked' => $originalLocked
		));
		$this->assertNotIdentical($signature0, $signature1);
	}
}

?>