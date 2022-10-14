<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console\command\create;

use lithium\util\Inflector;
use lithium\util\Text;

/**
 * Generate a View file in the `--library` namespace
 *
 * `li3 create view Posts index`
 * `li3 create --library=li3_plugin view Posts index`
 *
 */
class View extends \lithium\console\command\Create {

	/**
	 * Returns the name of the controller class, minus `'Controller'`.
	 *
	 * @param object $request
	 * @return string
	 */
	protected function _name($request) {
		return Inflector::camelize($request->action);
	}

	/**
	 * Get the plural data variable that is sent down from controller method.
	 *
	 * @param object $request
	 * @return string
	 */
	protected function _plural($request) {
		return Inflector::pluralize(Inflector::camelize($request->action, false));
	}

	/**
	 * Get the singular data variable that is sent down from controller methods.
	 *
	 * @param object $request
	 * @return string
	 */
	protected function _singular($request) {
		return Inflector::singularize(Inflector::camelize($request->action, false));
	}

	/**
	 * Override the save method to handle view specific params.
	 *
	 * @param array $params
	 * @return mixed
	 */
	protected function _save(array $params = []) {
		$params['path'] = Inflector::underscore($this->request->action);
		$params['file'] = $this->request->args(0);

		$contents = $this->_template();
		$result = Text::insert($contents, $params);

		if (!empty($this->_library['path'])) {
			$path = $this->_library['path'] . "/views/{$params['path']}/{$params['file']}";
			$file = str_replace('//', '/', "{$path}.php");
			$directory = dirname($file);

			if (!is_dir($directory)) {
				if (!mkdir($directory, 0755, true)) {
					return false;
				}
			}
			$directory = str_replace($this->_library['path'] . '/', '', $directory);
			if (file_exists($file)) {
				$prompt = "{$file} already exists. Overwrite?";
				$choices = ['y', 'n'];
				if ($this->in($prompt, compact('choices')) !== 'y') {
					return "{$params['file']} skipped.";
				}
			}
			if (is_int(file_put_contents($file, $result))) {
				return "{$params['file']}.php created in {$directory}.";
			}
		}
		return false;
	}
}

?>