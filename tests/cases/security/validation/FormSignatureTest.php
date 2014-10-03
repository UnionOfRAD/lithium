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
	public function testSignatureFailingForInvalidLockedFieldValue() {
		$components = array(
			'a%3A1%3A%7Bs%3A6%3A%22active%22%3Bs%3A4%3A%22true%22%3B%7D',
			'a%3A0%3A%7B%7D',
			'$2a$10$NuNTOeXv4OHpPJtbdAmfReTIDGVK87uiQcWRIRvL2rvsl7DV4vzVa'
		);
		$signature = join('::', $components);

		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'active' => 'true',
			'security' => compact('signature')
		)));
		$this->assertTrue(FormSignature::check($request));

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
		$components = array(
			'a%3A1%3A%7Bs%3A6%3A%22active%22%3Bs%3A4%3A%22true%22%3B%7D',
			'a%3A0%3A%7B%7D',
			'$2a$10$NuNTOeXv4OHpPJtbdAmfReTIDGVK87uiQcWRIRvL2rvsl7DV4vzVa'
		);
		$signature = join('::', $components);

		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'active' => 'true',
			'security' => compact('signature') + array('foo' => 'bar')
		)));
		$this->assertTrue(FormSignature::check($request));
	}

	public function testSignatureKeyForDifferentValues() {
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

	public function testSignatureCheckWithLockedFields() {
		$data = array(
			'fields' => array(
				'email' => 'foo@baz',
				'pass' => 'whatever',
			),
			'locked' => array(
				'active' => 'true'
			)
		);
		$signature = FormSignature::key($data);

		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'active' => 'true',
			'security' => compact('signature')
		)));
		$this->assertTrue(FormSignature::check($request));
	}
}

?>
