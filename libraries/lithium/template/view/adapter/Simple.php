<?php

namespace lithium\template\view\adapter;

use \Exception;
use \lithium\util\Set;
use \lithium\util\String;

/**
 * This view adapter renders content using simple string substitution, and is only useful for very
 * simple templates (no conditionals or looping) or testing.
 *
 */
class Simple extends \lithium\template\view\Renderer {

	protected $_classes = array();

	public function __construct($config = array()) {
		$defaults = array('classes' => array());
		parent::__construct($config + $defaults);
	}

	/**
	 * Renders content from a template file provided by `template()`.
	 *
	 * @param string $template
	 * @param array $data
	 * @param array $context
	 * @param array $options
	 * @return string
	 */
	public function render($template, $data = array(), $context = array(), $options = array()) {
		foreach ($data as $key => $val) {
			switch (true) {
				case is_object($val):
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
		return String::insert($template, $data, $options);
	}

	/**
	 * Returns a template string
	 *
	 * @param string $type
	 * @param array $options
	 * @return string
	 */
	public function template($type, $options) {
		return isset($options[$type]) ? $options[$type] : '';
	}
}

?>