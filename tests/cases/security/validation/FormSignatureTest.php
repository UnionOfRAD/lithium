<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\security\validation;

use lithium\action\Request;
use lithium\security\validation\FormSignature;

class FormSignatureTest extends \lithium\test\Unit {

	public function setUp() {
		FormSignature::config([
			'secret' => 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY'
		]);
	}

	public function testSucceedFields() {
		$signature = FormSignature::key([
			'fields' => [
				'email' => 'foo@baz',
				'pass' => 'whatever'
			]
		]);
		$request = new Request(['data' => [
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'security' => compact('signature')
		]]);
		$this->assertTrue(FormSignature::check($request));
	}

	public function testFailAddedFields() {
		$signature = FormSignature::key([
			'fields' => [
				'email' => 'foo@baz',
				'pass' => 'whatever'
			]
		]);
		$request = new Request(['data' => [
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'id' => 23,
			'security' => compact('signature')
		]]);
		$this->assertFalse(FormSignature::check($request));
	}

	public function testFailRemovedFields() {
		$signature = FormSignature::key([
			'fields' => [
				'email' => 'foo@baz',
				'pass' => 'whatever'
			]
		]);
		$request = new Request(['data' => [
			'email' => 'foo@baz',
			'security' => compact('signature')
		]]);
		$this->assertFalse(FormSignature::check($request));
	}

	public function testSucceedLocked() {
		$signature = FormSignature::key([
			'fields' => [
				'email' => 'foo@baz',
				'pass' => 'whatever'
			],
			'locked' => [
				'active' => 'true'
			]
		]);
		$request = new Request(['data' => [
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'active' => 'true',
			'security' => compact('signature')
		]]);
		$this->assertTrue(FormSignature::check($request));
	}

	/**
	 * Tests that `FormSignature` fails to generate a matching signature for data where locked
	 * values have been tampered with.
	 */
	public function testFailLockedFieldValueChange() {
		$signature = FormSignature::key([
			'fields' => [
				'email' => 'foo@baz',
				'pass' => 'whatever'
			],
			'locked' => [
				'active' => 'true'
			]
		]);
		$request = new Request(['data' => [
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'active' => 'false',
			'security' => compact('signature')
		]]);
		$this->assertFalse(FormSignature::check($request));
	}

	public function testFailRemovedLocked() {
		$signature = FormSignature::key([
			'fields' => [
				'email' => 'foo@baz',
				'pass' => 'whatever'
			],
			'locked' => [
				'active' => true
			]
		]);
		$request = new Request(['data' => [
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'security' => compact('signature')
		]]);
		$this->assertFalse(FormSignature::check($request));
	}

	public function testSucceedIgnoreAddedExcluded() {
		$signature = FormSignature::key([
			'fields' => [
				'email' => 'foo@baz',
				'pass' => 'whatever'
			],
			'excluded' => [
				'_editor' => 'wyishtml5'
			]
		]);
		$request = new Request(['data' => [
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'_editor' => 'wysithml5',
			'security' => compact('signature')
		]]);
		$this->assertTrue(FormSignature::check($request));
	}

	public function testSucceedExcludedButNotAdded() {
		$signature = FormSignature::key([
			'fields' => [
				'email' => 'foo@baz',
				'pass' => 'whatever'
			],
			'excluded' => [
				'_editor'
			]
		]);
		$request = new Request(['data' => [
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'security' => compact('signature')
		]]);
		$this->assertTrue(FormSignature::check($request));
	}

	/**
	 * Tests that `FormSignature` correctly ignores other fields in the `'security'` array when
	 * generating signatures.
	 */
	public function testIgnoreSecurityFields() {
		$signature = FormSignature::key([
			'fields' => [
				'email' => 'foo@baz',
				'pass' => 'whatever'
			],
			'locked' => [
				'active' => 'true'
			]
		]);
		$request = new Request(['data' => [
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'active' => 'true',
			'security' => compact('signature') + ['foo' => 'bar']
		]]);
		$this->assertTrue(FormSignature::check($request));
	}

	public function testFailsTamperedFieldsWithMany() {
		for ($original = [], $i = 0; $i < 100; $i++) {
			$original['foo' . $i] = 'bar' . $i;
		}
		$signature0 = FormSignature::key([
			'fields' => $original
		]);

		$changed = $original;
		$changed['foo10000'] = 'barAdded';
		$signature1 = FormSignature::key([
			'fields' => $changed
		]);
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $original;
		unset($changed['foo1']);
		$signature1 = FormSignature::key([
			'fields' => $changed
		]);
		$this->assertNotIdentical($signature0, $signature1);
	}

	public function testFailsTamperedLockedWithMany() {
		for ($original = [], $i = 0; $i < 100; $i++) {
			$original['foo' . $i] = 'bar' . $i;
		}
		$signature0 = FormSignature::key([
			'locked' => $original
		]);

		$changed = $original;
		$changed['foo90'] = 'barChanged';
		$signature1 = FormSignature::key([
			'locked' => $changed
		]);
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $original;
		$changed['foo10000'] = 'barAdded';
		$signature1 = FormSignature::key([
			'locked' => $changed
		]);
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $original;
		unset($changed['foo1']);
		$signature1 = FormSignature::key([
			'locked' => $changed
		]);
		$this->assertNotIdentical($signature0, $signature1);
	}

	public function testFailsTamperedFieldsAndLockedWithManyAndLockedChange() {
		for ($originalFields = [], $i = 0; $i < 20; $i++) {
			$originalFields['fooa' . $i] = 'bara' . $i;
		}
		for ($originalLocked = [], $i = 0; $i < 20; $i++) {
			$originalLocked['foob' . $i] = 'barb' . $i;
		}
		$signature0 = FormSignature::key([
			'fields' => $originalFields,
			'locked' => $originalLocked
		]);

		$changed = $originalLocked;
		$changed['foo90'] = 'barChanged';
		$signature1 = FormSignature::key([
			'fields' => $originalFields,
			'locked' => $changed
		]);
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $originalLocked;
		$changed['foo10000'] = 'barAdded';
		$signature1 = FormSignature::key([
			'fields' => $originalFields,
			'locked' => $changed
		]);
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $originalLocked;
		unset($changed['foob1']);
		$signature1 = FormSignature::key([
			'fields' => $originalFields,
			'locked' => $changed
		]);
		$this->assertNotIdentical($signature0, $signature1);
	}

	public function testFailsTamperedFieldsAndLockedWithManyAndFieldsChange() {
		for ($originalFields = [], $i = 0; $i < 20; $i++) {
			$originalFields['fooa' . $i] = 'bara' . $i;
		}
		for ($originalLocked = [], $i = 0; $i < 20; $i++) {
			$originalLocked['foob' . $i] = 'barb' . $i;
		}
		$signature0 = FormSignature::key([
			'fields' => $originalFields,
			'locked' => $originalLocked
		]);

		$changed = $originalFields;
		$changed['foo10000'] = 'barAdded';
		$signature1 = FormSignature::key([
			'fields' => $changed,
			'locked' => $originalLocked
		]);
		$this->assertNotIdentical($signature0, $signature1);

		$changed = $originalFields;
		unset($changed['fooa1']);
		$signature1 = FormSignature::key([
			'fields' => $changed,
			'locked' => $originalLocked
		]);
		$this->assertNotIdentical($signature0, $signature1);
	}
}

?>