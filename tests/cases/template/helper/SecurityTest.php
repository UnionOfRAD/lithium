<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\helper;

use lithium\aop\Filters;
use lithium\action\Request;
use lithium\template\helper\Form;
use lithium\template\helper\Security;
use lithium\security\validation\FormSignature;
use lithium\tests\mocks\template\helper\MockFormRenderer;

class SecurityTest extends \lithium\test\Unit {

	public $subject;

	public $context;

	public function tearDown() {
		Filters::clear('lithium\template\helper\Form');
	}

	public static function key($token) {
		return 'WORKING';
	}

	public static function hash($token) {
		return $token;
	}

	public function setUp() {
		$this->context = new MockFormRenderer(compact('request'));
		$this->subject = new Security(array('context' => $this->context));

		FormSignature::config(array(
			'secret' => 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY'
		));
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
			'#a%3A1%3A%7Bs%3A6%3A%22active%22%3Bs%3A4%3A%22true%22%3B%7D',
			'a%3A0%3A%7B%7D',
			'[a-z0-9]{128}#'
		);
		$this->assertPattern(join('::', $expected), $signature);

		$request = new Request(array('data' => array(
			'email' => 'foo@baz',
			'pass' => 'whatever',
			'active' => 'true',
			'security' => compact('signature')
		)));
		$this->assertTrue(FormSignature::check($request));
	}

	public function testFormSignatureWithLockedAndExcluded() {
		$form = new Form(array('context' => $this->context));
		$validator = 'lithium\tests\mocks\security\validation\MockFormSignature';

		$helper = new Security(array(
			'context' => $this->context,
			'classes' => array(
				'formSignature' => $validator
			)
		));

		$helper->sign($form);

		ob_start();
		$content = array(
			$form->create(null, array('url' => 'http:///')),
			$form->text('email', array('value' => 'foo@bar')),
			$form->password('pass'),
			$form->hidden('id', array('value' => 23)),
			$form->text('foo', array('value' => 'bar', 'exclude' => true)),
			$form->hidden('active', array('value' => 'true', 'exclude' => true, 'locked' => false)),
			$form->end()
		);
		ob_get_clean();

		$result = $validator::$compile[0]['in'];
		$expected = array(
			'fields' => array(
				'email', 'pass'
			),
			'excluded' => array(
				'foo',
				'active'
			),
			'locked' => array(
				'id' => 23
			)
		);
		$compiledSignature = $validator::$compile[0]['out'];

		$this->assertEqual($expected, $result);

		$request = new Request(array(
			'data' => array(
				'security' => array('signature' => $compiledSignature)
			)
		));
		$validator::check($request);

		$expected = $compiledSignature;
		$result = $validator::$parse[0]['in']['signature'];
		$this->assertEqual($expected, $result);

		$result = $validator::$parse[0]['out'];
		$expected = array(
			'excluded' => array(
				'active',
				'foo'
			),
			'locked' => array(
				'id' => 23
			)
		);
		$this->assertEqual($expected, $result);

		$validator::reset();
	}

	public function testFormSignatureWithLabelField() {
		$form = new Form(array('context' => $this->context));
		$this->subject->sign($form);

		ob_start();
		$content = array(
			$form->create(null, array('url' => 'http:///')),
			$form->label('foo'),
			$form->text('email', array('value' => 'foo@bar')),
			$form->end()
		);
		$signature = ob_get_clean();
		preg_match('/value="([^"]+)"/', $signature, $match);
		list(, $signature) = $match;
		$result = $signature;

		$data = array(
			'fields' => array(
				'email' => 'foo@bar',
			)
		);
		$expected = FormSignature::key($data);
		$this->assertEqual($expected, $result);
	}

	public function testFormSignatureWithMethodPUT() {
		$form = new Form(array('context' => $this->context));
		$this->subject->sign($form);

		ob_start();
		$content = array(
			$form->create(null, array('url' => 'http:///', 'method' => 'PUT')),
			$form->text('email', array('value' => 'foo@bar')),
			$form->end()
		);
		$signature = ob_get_clean();
		preg_match('/value="([^"]+)"/', $signature, $match);
		list(, $signature) = $match;

		$request = new Request(array('data' => array(
			'_method' => 'PUT',
			'email' => 'foo@baz',
			'security' => compact('signature')
		)));
		$this->assertTrue(FormSignature::check($request));
	}
}

?>