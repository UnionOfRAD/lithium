<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\template\view\adapter;

use Exception;
use lithium\util\Set;
use lithium\util\Text;

/**
 * This view adapter renders content using simple string substitution, and is only useful for very
 * simple templates (no conditionals or looping) or testing.
 *
 */
class Simple extends \lithium\template\view\Renderer {

	/**
	 * Renders content from a template file provided by `template()`.
	 *
	 * @param string $template
	 * @param array $data
	 * @param array $options
	 * @return string
	 */
	public function render($template, $data = [], array $options = []) {
		$defaults = ['context' => []];
		$options += $defaults;

		$context = [];
		$this->_context = $options['context'] + $this->_context;

		foreach (array_keys($this->_context) as $key) {
			$context[$key] = $this->__get($key);
		}
		$data = array_merge($this->_toString($context), $this->_toString($data));
		return Text::insert($template, $data, $options);
	}

	/**
	 * Returns a template string
	 *
	 * @param string $type
	 * @param array $options
	 * @return string
	 */
	public function template($type, $options) {
		if (isset($options[$type])) {
			return $options[$type];
		}
		return isset($options['template']) ? $options['template'] : '';
	}

	/**
	 * Renders `$data` into an easier to understand, or flat, array.
	 *
	 * @param array $data Data to traverse.
	 * @return array
	 */
	protected function _toString($data) {
		foreach ($data as $key => $val) {
			switch (true) {
				case is_object($val) && !$val instanceof \Closure:
					try {
						$data[$key] = (string) $val;
					} catch (Exception $e) {
						$data[$key] = '';
					}
				break;
				case is_array($val):
					$data = array_merge($data, Set::flatten($val));
				break;
			}
		}
		return $data;
	}
}

?>