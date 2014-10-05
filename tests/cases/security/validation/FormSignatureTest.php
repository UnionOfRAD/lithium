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

	public function testSucceedFields() {
		$signature = FormSignature::key(array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever'
			)
		));
		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'security' => compact('signature')
		)));
		$this->assertTrue(FormSignature::check($request));
	}

	public function testFailAddedFields() {
		$signature = FormSignature::key(array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever'
			)
		));
		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'id' => 23,
			'security' => compact('signature')
		)));
		$this->assertFalse(FormSignature::check($request));
	}

	public function testFailRemovedFields() {
		$signature = FormSignature::key(array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever'
			)
		));
		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'security' => compact('signature')
		)));
		$this->assertFalse(FormSignature::check($request));
	}

	public function testSucceedLocked() {
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
			'security' => compact('signature')
		)));
		$this->assertTrue(FormSignature::check($request));
	}

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

	public function testFailRemovedLocked() {
		$signature = FormSignature::key(array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever'
			),
			'locked' => array(
				'active' => true
			)
		));
		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'security' => compact('signature')
		)));
		$this->assertFalse(FormSignature::check($request));
	}

	public function testSucceedIgnoreAddedExcluded() {
		$signature = FormSignature::key(array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever'
			),
			'excluded' => array(
				'_editor'
			)
		));
		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'_editor' => 'wysithml5',
			'security' => compact('signature')
		)));
		$this->assertTrue(FormSignature::check($request));
	}

	public function testSucceedExcludedButNotAdded() {
		$signature = FormSignature::key(array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever'
			),
			'excluded' => array(
				'_editor'
			)
		));
		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'security' => compact('signature')
		)));
		$this->assertTrue(FormSignature::check($request));
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
}

?>