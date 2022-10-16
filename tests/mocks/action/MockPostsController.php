<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\tests\mocks\action;

use Exception;

class MockPostsController extends \lithium\action\Controller {

	public $stopped = false;

	public function index($test = false) {
		if ($test) {
			return ['foo' => 'bar'];
		}
		return 'List of posts';
	}

	public function delete($id = null) {
		if (empty($id)) {
			return $this->redirect('/posts', ['exit' => false]);
		}
		return "Deleted {$id}";
	}

	public function send() {
		$this->redirect('/posts', ['exit' => true]);
	}

	public function type($raw = false) {
		return ['data' => 'test'];
	}

	public function notFound($id = null) {
		$this->response->status(404);
		$this->render(['json' => $this->response->status]);
	}

	public function view($id = null) {
		$this->render(['text', 'data' => 'This is a post']);
	}

	public function view2($id = null) {
		$this->render(['template' => 'view']);
	}

	public function view3($id = null) {
		$this->render(['layout' => false, 'template' => 'view']);
	}

	public function changeTemplate() {
		$this->_render['template'] = 'foo';
	}

	protected function _safe() {
		throw new Exception('Something wrong happened');
	}

	public function access($var) {
		return $this->{$var};
	}

	protected function _stop($status = 0) {
		$this->stopped = true;
	}
}

?>