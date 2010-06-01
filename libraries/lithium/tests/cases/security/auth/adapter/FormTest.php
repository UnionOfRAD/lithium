<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\security\auth\adapter;

use \lithium\action\Request;
use \lithium\data\entity\Record;
use \lithium\security\auth\adapter\Form;

class FormTest extends \lithium\test\Unit {

	public static function first(array $options = array()) {
		return new Record(array('data' => $options['conditions']));
	}

	public function testLogin() {
		$subject = new Form(array('model' => __CLASS__));
		$request = new Request();
		$request->data = array('username' => 'Person', 'password' => 'password');

		$result = $subject->check($request);
		$expected = array('username' => 'Person', 'password' => sha1('password'));
		$this->assertEqual($expected, $result);
	}

	public function testLoginWithFilters() {
		$subject = new Form(array('model' => __CLASS__, 'filters' => array(
			'username' => 'sha1'
		)));
		$request = new Request();
		$request->data = array('username' => 'Person', 'password' => 'password');

		$result = $subject->check($request);
		$expected = array('username' => sha1('Person'), 'password' => sha1('password'));
		$this->assertEqual($expected, $result);
	}

	/**
	 * Tests that `Form::set()` passes data through unmodified, even with invalid options.
	 *
	 * @return void
	 */
	public function testSetPassthru() {
		$subject = new Form(array('model' => __CLASS__));
		$user = array('id' => 5, 'name' => 'bob');

		$result = $subject->set($user);
		$this->assertIdentical($user, $result);
	}
}

?>