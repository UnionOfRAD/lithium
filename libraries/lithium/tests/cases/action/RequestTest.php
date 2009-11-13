<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\action;

use \lithium\action\Request;

class RequestTest extends \lithium\test\Unit {

	public $request = null;

	public function setUp() {
		$this->request = new Request(array('init' => false));
	}

	/**
	 * Tests that request parameters in the `$params` property are accessible as magic properties
	 * through `__get()`.
	 *
	 * @return void
	 */
	public function testMagicParamsAccess() {
		$this->assertNull($this->request->action);
		$this->assertFalse(isset($this->request->params['action']));

		$expected = $this->request->params['action'] = 'index';
		$result = $this->request->action;
		$this->assertEqual($expected, $result);
	}

	public function testFilesNormalization() {
		$result = $this->request->normalizeFiles(array(
			'fileA' => array(
				'name' => 'fileA.txt', 'type' => 'text/plain',
				'tmp_name' => '', 'error' => 4, 'size' => 0
			),
		    'fileB' => array(
				'name' => array(
					'a' => 'fileBa', 'b' => array('c' => ''), 'd' => array('e' => array('f' => ''))
				),
				'type' => array(
					'a' => '', 'b' => array('c' => ''), 'd' => array('e' => array('f' => ''))
				),
				'tmp_name' => array(
					'a' => '', 'b' => array('c' => ''), 'd' => array('e' => array('f' => ''))
				),
				'error' => array(
					'a' => 4, 'b' => array('c' => 4), 'd' => array('e' => array('f' => 4))
				),
				'size' => array(
					'a' => 0, 'b' => array('c' => 0), 'd' => array('e' => array('f' => 0)),
				)
			)
		));
	}
}

?>