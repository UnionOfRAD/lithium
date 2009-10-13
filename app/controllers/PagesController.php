<?php

namespace app\controllers;

use \lithium\util\Inflector;

class PagesController extends \lithium\action\Controller {

	public $helpers = array('Html');

	public function view() {
		$path = func_get_args();

		if (!count($path)) {
			$path = array('home');
		}

		$count = count($path);
		$page = $subpage = $title = null;

		$page = (!empty($path[0]) ? $path[0] : $page);
		$subpage = (!empty($path[1]) ? $path[1] : $subpage);
		$title = (!empty($path[$count - 1]) ? Inflector::humanize($path[$count - 1]) : $title);

		$this->set(compact('page', 'subpage', 'title'));
		$this->render(join('/', $path));
	}
}

?>