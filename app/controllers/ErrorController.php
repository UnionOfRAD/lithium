<?php

namespace app\controllers;

use lithium\analysis\Inspector;
use lithium\core\ClassNotFoundException;
use lithium\core\ErrorHandler;

class ErrorController extends \lithium\action\Controller {

	public function index() {
		$exception = new ClassNotFoundException("Class 'Foo' of type 'bar' not found.");
		$line = $exception->getLine();

		/**
		 * Should these be called from the view or go into a helper?
		 * I wasn't sure, but I think it would be cleaner if the
		 * exception was the only thing passed to the view.
		 */
		$sourceCode = Inspector::lines($exception->getFile(), range($line - 3, $line + 3));
		$stackTrace = ErrorHandler::trace($exception->getTrace());
		$this->set(compact('exception', 'sourceCode', 'stackTrace'));
	}
}

?>