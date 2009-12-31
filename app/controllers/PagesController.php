<?php

namespace app\controllers;

use \lithium\util\Inflector;

class PagesController extends \lithium\action\Controller {

	public function view() {
		$path = func_get_args();

		if (empty($path)) {
			$path = array('home');
		}
		$this->render(join('/', $path));
	}
}

?>