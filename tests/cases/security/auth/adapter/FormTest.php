<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\security\auth\adapter;

use lithium\security\Password;
use lithium\data\entity\Record;
use lithium\security\auth\adapter\Form;

class FormTest extends \lithium\test\Unit {

	public static function first(array $options = array()) {
		return new Record(array('data' => $options['conditions']));
	}

	public static function validatorTest(array $options = array()) {
		return new Record(array('data' => array(
			'username' => 'Bob',
			'password' => Password::hash('s3cure'),
			'group' => 'editors'
		)));
	}

	public function testLogin() {
		$subject = new Form(array(
			'model' => __CLASS__,
			'filters' => array('password' => array('lithium\util\String', 'hash')),
			'validators' => array('password' => false)
		));

		$request = (object) array('data' => array(
			'username' => 'Person', 'password' => 'password'
		));

		$result = $subject->check($request);
		$expected = array('username' => 'Person');
		$this->assertEqual($expected, $result);
	}

	public function testLoginWithFilters() {
		$subject = new Form(array(
			'model' => __CLASS__,
			'filters' => array(
				'username' => 'sha1',
				'password' => array('lithium\util\String', 'hash')
			),
			'validators' => array()
		));
		$data = array('username' => 'Person', 'password' => 'password');
		$password = 'b109f3bbbc244eb82441917ed06d618b9008dd09b3befd1b5e07394c706a8bb980b1d7';
		$password .= '785e5976ec049b46df5f1326af5a2ea6d103fd07c95385ffab0cacbc86';

		$expected = array('username' => sha1('Person')) + compact('password');
		$this->assertEqual($expected, $subject->check((object) compact('data')));

		$subject = new Form(array(
			'model' => __CLASS__,
			'validators' => array(),
			'fields' => array('username', 'password', 'date'),
			'filters' => array(
				'date' => function($date) {
					return "{$date['year']}-{$date['month']}-{$date['day']}";
				}
			)
		));

		$data = array('username' => 'bob', 'password' => 'foo', 'date' => array(
			'year' => '2011', 'month' => '06', 'day' => '29'
		));

		$expected = array('username' => 'bob', 'password' => 'foo', 'date' => '2011-06-29');
		$this->assertEqual($expected, $subject->check((object) compact('data')));
	}

	/**
	 * Tests that attempted exploitation via malformed credential submission is not possible.
	 */
	public function testLoginWithArray() {
		$subject = new Form(array('model' => __CLASS__, 'validators' => array()));
		$request = (object) array('data' => array(
			'username' => array('!=' => ''), 'password' => ''
		));
		$result = $subject->check($request);
		$this->assertEqual('Array', $result['username']);
	}

	/**
	 * Tests that `Form::set()` passes data through unmodified, even with invalid options.
	 */
	public function testSetPassthru() {
		$subject = new Form(array('model' => __CLASS__));
		$user = array('id' => 5, 'name' => 'bob');

		$result = $subject->set($user);
		$this->assertIdentical($user, $result);
	}

	/**
	 * Tests configuration of the `'fields'` setting where some form fields are mapped directly to
	 * database fields (i.e. `array('field')`) and some are mapped manually (i.e.
	 * `array('form_field' => 'database_field')`) in a single mixed array.
	 */
	public function testMixedFieldMapping() {
		$subject = new Form(array(
			'model' => __CLASS__,
			'fields' => array('username' => 'name', 'password', 'group'),
			'validators' => array()
		));
		$request = (object) array('data' => array(
			'username' => 'Bob', 'password' => 's3cure', 'group' => 'editors'
		));

		$expected = array('name' => 'Bob', 'password' => 's3cure', 'group' => 'editors');
		$this->assertEqual($expected, $subject->check($request));
	}

	/**
	 * Tests that parameter validators are correctly applied to form data after the authentication
	 * query has occurred.
	 */
	public function testParameterValidators() {
		$subject = new Form(array(
			'model' => __CLASS__,
			'query' => 'validatorTest',
			'fields' => array('username', 'password', 'group'),
			'validators' => array(
				'password' => function($form, $data) {
					return Password::check($form, $data);
				},
				'group' => function($form) {
					return $form === 'editors';
				}
			)
		));

		$data = array('username' => 'Bob', 'password' => 's3cure', 'group' => 'editors');
		$result = $subject->check((object) compact('data'));
		$this->assertEqual(array_keys($data), array_keys($result));

		$this->assertEqual('Bob', $result['username']);
		$this->assertEqual('editors', $result['group']);
		$this->assertTrue(Password::check('s3cure', $result['password']));
	}

	/**
	 * Tests that parameters with validators are omitted from query conditions.
	 */
	public function testOmitValidatedParams() {
		$subject = new Form(array(
			'model' => __CLASS__,
			'fields' => array('username', 'password', 'group'),
			'validators' => array(
				'password' => function($form, $data) { return true; },
				'group' => function($form) { return true; }
			)
		));

		$data = array('username' => 'Bob', 'password' => 's3cure', 'group' => 'editors');
		$result = $subject->check((object) compact('data'));
		$this->assertEqual(array('username' => 'Bob'), $result);
	}
}

?>