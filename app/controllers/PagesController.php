<?php

namespace app\controllers;

class PagesController extends \lithium\action\Controller {

	public function view() {
		$path = func_get_args();

		if (empty($path)) {
			$path = array('home');
		}
		$this->render(array('template' => join('/', $path)));
	}
}

?>