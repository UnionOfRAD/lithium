<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\cases\security\auth\adapter;

use lithium\security\Password;
use lithium\data\entity\Record;
use lithium\security\auth\adapter\Form;

class FormTest extends \lithium\test\Unit {

	public static function first(array $options = []) {
		if (!$options['conditions']) {
			return null;
		}
		return new Record(['data' => $options['conditions']]);
	}

	public static function validatorTest(array $options = []) {
		return new Record(['data' => [
			'username' => 'Bob',
			'password' => Password::hash('s3cure'),
			'group' => 'editors'
		]]);
	}

	public static function meta($key) {
		switch ($key) {
			case 'name':
				return __CLASS__;
		}
	}

	/**
	 * Used by `testValidatorWithFieldMapping` and makes sure that the
	 * custom password field name isn't sent in the query
	 *
	 * @param array $options
	 * @return object
	 */
	public static function validatorFieldMappingTest(array $options = []) {
		if (isset($options['conditions']['user.password'])) {
			return null;
		}
		return new Record(['data' => [
			'user.name' => 'Foo',
			'user.password' => 'bar'
		]]);
	}

	/**
	 * Tests a check for the model class prior to attempted use.
	 */
	public function testModel() {
		$this->assertException("Model class 'ModelDoesNotExist' not found.", function() {
			$subject = new Form([
				'model' => 'ModelDoesNotExist',
				'fields' => ['username'],
				'validators' => ['password' => false]
			]);
		});
	}

	/**
	 * Tests a simple user lookup. Note that we're not using the password validator; due to the
	 * limitations of this classes first() mock method, password will not be in the dataset
	 * returned by Form::check().
	 */
	public function testLogin() {
		$subject = new Form([
			'model' => __CLASS__,
			'fields' => ['username'],
			'validators' => ['password' => false]
		]);

		$request = (object) ['data' => [
			'username' => 'Person'
		]];

		$result = $subject->check($request);
		$expected = ['username' => 'Person'];
		$this->assertEqual($expected, $result);

		$subject = new Form([
			'model' => __CLASS__,
			'fields' => [],
			'validators' => ['password' => false]
		]);

		$request = (object) ['data' => []];
		$this->assertFalse($subject->check($request));
	}

	public function testLoginWithFilters() {
		$subject = new Form([
			'model' => __CLASS__,
			'fields' => ['username'],
			'filters' => ['username' => 'sha1'],
			'validators' => ['password' => false]
		]);

		$request = (object) ['data' => ['username' => 'Person']];

		$expected = ['username' => sha1('Person')];
		$result = $subject->check($request);
		$this->assertEqual($expected, $result);

		$subject = new Form([
			'model' => __CLASS__,
			'fields' => ['username', 'date'],
			'filters' => [
				'username' => false,
				'date' => function($date) {
					return "{$date['year']}-{$date['month']}-{$date['day']}";
				}
			],
			'validators' => ['password' => false]
		]);

		$request = (object) ['data' => [
			'username' => 'bob',
			'date' => [
				'year' => '2012', 'month' => '06', 'day' => '29'
			]
		]];

		$expected = ['username' => 'bob', 'date' => '2012-06-29'];
		$result = $subject->check($request);
		$this->assertEqual($expected, $result);
	}

	public function testUncallableFilter() {
		$subject = new Form([
			'model' => __CLASS__,
			'filters' => [
				'username' => true
			]
		]);

		$request = (object) ['data' => ['username' => 'Test']];
		$expected = 'Authentication filter for `username` is not callable.';

		$this->assertException($expected, function() use ($subject, $request) {
			$subject->check($request);
		});
	}

	public function testGenericFilter() {
		$subject = new Form([
			'model' => __CLASS__,
			'fields' => ['username', 'password', 'group', 'secret'],
			'filters' => [
				function($form) {
					unset($form['secret']);
					return $form;
				}
			],
			'validators' => ['password' => false]
		]);

		$request = (object) ['data' => [
			'username' => 'bob',
			'group' => 'editors',
			'secret' => 'value',
			'password' => 'foo!'
		]];

		$result = $subject->check($request);
		$expected = ['username' => 'bob', 'group' => 'editors', 'password' => 'foo!'];
		$this->assertEqual($expected, $result);

		$subject = new Form([
			'model' => __CLASS__,
			'fields' => ['username', 'password', 'group', 'secret'],
			'validators' => ['password' => false]
		]);

		$request = (object) ['data' => [
			'username' => 'bob',
			'group' => 'editors',
			'secret' => 'value',
			'password' => 'foo!'
		]];

		$result = $subject->check($request);
		$expected = [
			'username' => 'bob', 'group' => 'editors', 'password' => 'foo!', 'secret' => 'value'
		];
		$this->assertEqual($expected, $result);
	}

	public function testUncallableGenericFilter() {
		$subject = new Form([
			'model' => __CLASS__,
			'filters' => [
				true
			]
		]);

		$request = (object) ['data' => ['username' => 'Test']];
		$expected = 'Authentication filter is not callable.';

		$this->assertException($expected, function() use ($subject, $request) {
			$subject->check($request);
		});
	}

	/**
	 * Tests that attempted exploitation via malformed credential submission is not possible.
	 */
	public function testLoginWithArray() {
		$subject = new Form([
			'model' => __CLASS__,
			'validators' => ['password' => false]
		]);

		$request = (object) ['data' => [
			'username' => ['!=' => ''], 'password' => ''
		]];

		$result = $subject->check($request);
		$this->assertNull($result['username']);
	}

	/**
	 * Tests that `Form::set()` passes data through unmodified, even with invalid options.
	 */
	public function testSetPassthru() {
		$subject = new Form(['model' => __CLASS__]);
		$user = ['id' => 5, 'name' => 'bob'];

		$result = $subject->set($user);
		$this->assertIdentical($user, $result);
	}

	/**
	 * Tests configuration of the `'fields'` setting where some form fields are mapped directly to
	 * database fields (i.e. `['field']`) and some are mapped manually (i.e.
	 * `['form_field' => 'database_field']`) in a single mixed array.
	 */
	public function testMixedFieldMapping() {
		$subject = new Form([
			'model' => __CLASS__,
			'fields' => ['username' => 'name', 'group'],
			'validators' => []
		]);

		$request = (object) ['data' => [
			'username' => 'Bob', 'group' => 'editors'
		]];

		$expected = ['name' => 'Bob', 'group' => 'editors'];
		$this->assertEqual($expected, $subject->check($request));
	}

	public function testDefaultValidator() {
		$subject = new Form([
			'model' => __CLASS__,
			'fields' => ['username', 'password', 'group'],
			'validators' => ['password' => false]
		]);

		$request = (object) ['data' => [
			'username' => 'Bob', 'password' => 's3cure', 'group' => 'editors'
		]];

		$result = $subject->check($request);
		$expected = ['username' => 'Bob', 'group' => 'editors', 'password' => 's3cure'];
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that parameter validators are correctly applied to form data after the authentication
	 * query has occurred.
	 */
	public function testParameterValidators() {
		$subject = new Form([
			'model' => __CLASS__,
			'query' => 'validatorTest',
			'validators' => [
				'password' => function($form, $data) {
					return Password::check($form, $data);
				},
				'group' => function($form) {
					return $form === 'editors';
				}
			]
		]);

		$request = (object) ['data' => [
			'username' => 'Bob', 'password' => 's3cure', 'group' => 'editors'
		]];

		$result = $subject->check($request);
		$this->assertEqual(array_keys($request->data), array_keys($result));

		$this->assertEqual('Bob', $result['username']);
		$this->assertEqual('editors', $result['group']);
		$this->assertTrue(Password::check('s3cure', $result['password']));
	}

	/**
	 * Tests that parameters with validators are omitted from query conditions.
	 */
	public function testOmitValidatedParams() {
		$subject = new Form([
			'model' => __CLASS__,
			'validators' => [
				'password' => function($form, $data) { return true; },
				'group' => function($form) { return true; }
			]
		]);

		$request = (object) ['data' => [
			'username' => 'Bob', 'password' => 's3cure', 'group' => 'editors'
		]];

		$result = $subject->check($request);
		$this->assertEqual(['username' => 'Bob'], $result);
	}

	public function testParameterValidatorsFail() {
		$subject = new Form([
			'model' => __CLASS__,
			'validators' => [
				'password' => function($form, $data) { return false; }
			]
		]);

		$request = (object) ['data' => [
			'username' => 'Bob', 'password' => 's3cure', 'group' => 'editors'
		]];

		$result = $subject->check($request);
		$this->assertFalse($result);
	}

	public function testUncallableValidator() {
		$subject = new Form([
			'model' => __CLASS__,
			'validators' => ['password' => true]
		]);

		$request = (object) ['data' => ['username' => 'Bob']];
		$expected = 'Authentication validator for `password` is not callable.';

		$this->assertException($expected, function() use ($subject, $request) {
			$subject->check($request);
		});
	}

	public function testGenericValidator() {
		$self = $this;
		$subject = new Form([
			'model' => __CLASS__,
			'query' => 'validatorTest',
			'validators' => [
				function($data, $user) use ($self) {
					return true;
				}
			]
		]);

		$request = (object) ['data' => [
			'username' => 'Bob', 'password' => 's3cure', 'group' => 'editors'
		]];

		$result = $subject->check($request);
		$this->assertEqual(array_keys($request->data), array_keys($result));
	}

	public function testUncallableGenericValidator() {
		$subject = new Form([
			'model' => __CLASS__,
			'validators' => [true, 'password' => false]
		]);

		$request = (object) ['data' => ['username' => 'Bob']];
		$expected = 'Authentication validator is not callable.';

		$this->assertException($expected, function() use ($subject, $request) {
			$subject->check($request);
		});
	}

	/**
	 * Tests that the `Form` adapter can be configured to do simple hash-based password
	 * authentication.
	 */
	public function testHashedPasswordAuth() {
		$subject = new Form([
			'model' => __CLASS__,
			'filters' => ['password' => 'sha1'],
			'validators' => ['password' => false]
		]);

		$request = (object) ['data' => ['username' => 'Bob', 'password' => 's3kr1t']];
		$expected = [
			'username' => 'Bob',
			'password' => 'ff44e879c7e013b38e4b970e8a5d47c7f283eed1'
		];
		$this->assertEqual($expected, $subject->check($request));
	}

	public function testValidatorWithFieldMapping() {
		$subject = new Form([
			'model' => __CLASS__,
			'query' => 'validatorFieldMappingTest',
			'fields' => ['name' => 'user.name', 'password' => 'user.password'],
			'validators' => [
				'password' => function ($form, $data) {
					if ($form === $data) {
						return true;
					}
					return false;
				}
			]
		]);

		$request = (object) ['data' => ['name' => 'Foo', 'password' => 'bar']];
		$this->assertNotEmpty($subject->check($request));
	}
}

?>