<?php

namespace lithium\tests\mocks\template\view\adapters;

use lithium\util\Text;

class TestRenderer extends \lithium\template\view\adapter\File implements \ArrayAccess {
	public static $templateData = [];
	public static $renderData = [];

	public function template($type, array $params) {
		foreach ((array) $this->_paths[$type] as $path) {
			if (!file_exists($path = Text::insert($path, $params))) {
				continue;
			}
			static::$templateData[] = compact('type', 'params') + [
				'return' => $path
			];
			return $path;
		}
		static::$templateData[] = compact('type', 'params') + [
			'return' => false
		];
		return false;
	}

	public function render($template, $data = [], array $options = []) {
		static::$renderData[] = compact('template', 'data', 'options');
		ob_start();
		include $template;
		return ob_get_clean();
	}
}

?>