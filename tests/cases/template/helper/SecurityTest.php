<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\helper;

use lithium\action\Request;
use lithium\template\helper\Form;
use lithium\template\helper\Security;
use lithium\security\validation\FormSignature;
use lithium\tests\mocks\template\helper\MockFormRenderer;

class SecurityTest extends \lithium\test\Unit {

	public $subject;

	public $context;

	public static function key($token) {
		return 'WORKING';
	}

	public static function hash($token) {
		return $token;
	}

	public function setUp() {
		$this->context = new MockFormRenderer(compact('request'));
		$this->subject = new Security(array('context' => $this->context));
	}

	/**
	 * Tests that the helper correctly generates a security token field.
	 */
	public function testRequestToken() {
		$result = explode(' ', $this->subject->requestToken());

		$this->assertEqual('<input', $result[0]);
		$this->assertEqual('type="hidden"', $result[1]);
		$this->assertEqual('name="security[token]"', $result[2]);
		$this->assertEqual('/>', $result[4]);

		$result = explode('=', $result[3]);
		$this->assertEqual('value', $result[0]);

		$result = trim($result[1], '"');
		$this->assertPattern('/^\$\d\w\$\d{2}\$[A-Za-z0-9\.\/]{53}$/', $result);
	}

	/**
	 * Tests that the helper can be constructed with a custom configuration.
	 */
	public function testConstruct() {
		$this->subject = new Security(array('context' => $this->context, 'classes' => array(
			'password' => __CLASS__,
			'requestToken' => __CLASS__
		)));
		$this->assertPattern('/value="WORKING"/', $this->subject->requestToken());
	}

	/**
	 * Tests that the `Security` helper correctly binds to the `Form` helper to collect field
	 * information and generate a signature.
	 */
	public function testFormSignatureGeneration() {
		$form = new Form(array('context' => $this->context));
		$this->subject->sign($form);

		ob_start();
		$content = array(
			$form->create(null, array('url' => 'http:///')),
			$form->text('email', array('value' => 'foo@bar')),
			$form->password('pass'),
			$form->hidden('active', array('value' => 'true')),
			$form->end()
		);
		$signature = ob_get_clean();
		preg_match('/value="([^"]+)"/', $signature, $match);
		list(, $signature) = $match;

		$expected = array(
			'a%3A1%3A%7Bs%3A6%3A%22active%22%3Bs%3A4%3A%22true%22%3B%7D',
			'a%3A0%3A%7B%7D',
			'$2a$10$NuNTOeXv4OHpPJtbdAmfReFiSmFw5hmc6sSy8qwns6/DWNSSOjR1y'
		);
		$this->assertEqual(join('::', $expected), $signature);

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