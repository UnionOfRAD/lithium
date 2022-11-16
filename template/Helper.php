<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\template;

use lithium\util\Text;
use lithium\core\AutoConfigurable;

/**
 * Abstract class for template helpers to extend.
 * Supplies the basic functionality of _render and _options,
 * as well as escaping.
 *
 */
abstract class Helper {

	use AutoConfigurable;

	/**
	 * Maps helper method names to content types as defined by the `Media` class, where key are
	 * method names, and values are the content type that the method name outputs a link to.
	 *
	 * @var array
	 */
	public $contentMap = [];

	/**
	 * Holds string templates which will be merged into the rendering context.
	 *
	 * @var array
	 */
	protected $_strings = [];

	/**
	 * The Renderer object this Helper is bound to.
	 *
	 * @var lithium\template\view\Renderer
	 * @see lithium\template\view\Renderer
	 */
	protected $_context = null;

	/**
	 * This property can be overwritten with any class dependencies a helper subclass has.
	 *
	 * @var array
	 */
	protected $_classes = [];

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = ['classes' => 'merge', 'context'];

	/**
	 * List of minimized HTML attributes.
	 *
	 * @var array
	 */
	protected $_minimized = [
		'compact', 'checked', 'declare', 'readonly', 'disabled', 'selected', 'defer', 'ismap',
		'nohref', 'noshade', 'nowrap', 'multiple', 'noresize', 'async', 'autofocus'
	];

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration options.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['handlers' => [], 'context' => null];
		$this->_autoConfig($config + $defaults, $this->_autoConfig);
		$this->_autoInit($config);
	}

	/**
	 * Imports local string definitions into rendering context.
	 *
	 * @return void
	 */
	protected function _init() {
		if (!$this->_context) {
			return;
		}
		$this->_context->strings($this->_strings);

		if ($this->_config['handlers']) {
			$this->_context->handlers($this->_config['handlers']);
		}
	}

	/**
	 * Escapes values according to the output type of the rendering context. Helpers that output to
	 * non-HTML/XML contexts should override this method accordingly.
	 *
	 * @param string $value
	 * @param mixed $method
	 * @param array $options
	 * @return mixed
	 */
	public function escape($value, $method = null, array $options = []) {
		$defaults = ['escape' => true];
		$options += $defaults;

		if ($options['escape'] === false) {
			return $value;
		}
		if (is_array($value)) {
			return array_map([$this, __FUNCTION__], $value);
		}
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Converts a set of parameters to HTML attributes into a string.
	 *
	 * @see lithium\template\view\Renderer::__call()
	 * @param array|string $params The parameters where key is the attribute name and
	 *        the value the attribute value. When string will simply prepend with the
	 *        prepend-string (by default `' '`) unless $params is falsey in which case
	 *        an empty string is returned. This alternative syntax is used by the method
	 *        internally.
	 * @param string $method When used as a context handler, the method the handler
	 *        was called for I.e. `'wrap'`. Currently not used by the method.
	 * @param array $options Available options are:
	 *        - `'escape'` _boolean_: Indicates whether the output should be HTML-escaped.
	 *          Defaults to `true`.
	 *        - `'prepend'` _string_: String to prepend to each attribute pair and the final
	 *          result. Defaults to `' '`.
	 *        - `'append'` _string_: String to append to result. Defaults to `''`.
	 * @return string Attribute string.
	 */
	public function attributes($params, $method = null, array $options = []) {
		$defaults = ['escape' => true, 'prepend' => ' ', 'append' => ''];
		$options += $defaults;
		$result = [];

		if (!is_array($params)) {
			return !$params ? '' : $options['prepend'] . $params;
		}
		foreach ($params as $key => $value) {
			if ($next = $this->_attribute($key, $value, $options)) {
				$result[] = $next;
			}
		}
		return $result ? $options['prepend'] . implode(' ', $result) . $options['append'] : '';
	}

	/**
	 * Convert a key/value pair to a valid HTML attribute.
	 *
	 * @param string $key The key name of the HTML attribute.
	 * @param mixed $value The HTML attribute value.
	 * @param array $options The options used when converting the key/value pair to attributes:
	 *              - `'escape'` _boolean_: Indicates whether `$key` and `$value` should be
	 *                HTML-escaped. Defaults to `true`.
	 *              - `'format'` _string_: The format string. Defaults to `'%s="%s"'`.
	 * @return string Returns an HTML attribute/value pair, in the form of `'$key="$value"'`.
	 */
	protected function _attribute($key, $value, array $options = []) {
		$defaults = ['escape' => true, 'format' => '%s="%s"'];
		$options += $defaults;

		if (in_array($key, $this->_minimized)) {
			$isMini = ($value === 1 || $value === true || $value === $key);
			if (!($value = $isMini ? $key : $value)) {
				return null;
			}
		}
		$value = (string) $value;

		if ($options['escape']) {
			return sprintf($options['format'], $this->escape($key), $this->escape($value));
		}
		return sprintf($options['format'], $key, $value);
	}

	/**
	 * Takes the defaults and current options, merges them and returns options which have
	 * the default keys removed and full set of options as the scope.
	 *
	 * @param array $defaults
	 * @param array $scope the complete set of options
	 * @return array $scope, $options
	 */
	protected function _options(array $defaults, array $scope) {
		$scope += $defaults;
		$options = array_diff_key($scope, $defaults);
		return [$scope, $options];
	}

	/**
	 * Render a string template after applying context filters
	 * Use examples in the Html::link() method:
	 * `return $this->_render(__METHOD__, 'link', compact('title', 'url', 'options'), $scope);`
	 *
	 * @param string $method name of method that is calling the render (for context filters)
	 * @param string $string template key (in Helper::_strings) to render
	 * @param array $params associated array of template inserts {:key} will be replaced by value
	 * @param array $options Available options:
	 *              - `'handlers'` _array_: Before inserting `$params` inside the string template,
	 *              `$this->_context`'s handlers are applied to each value of `$params` according
	 *              to the key (e.g `$params['url']`, which is processed by the `'url'` handler
	 *              via `$this->_context->applyHandler()`).
	 *              The `'handlers'` option allow to set custom mapping beetween `$params`'s key and
	 *              `$this->_context`'s handlers. e.g. the following handler:
	 *              `'handlers' => ['url' => 'path']` will make `$params['url']` to be
	 *              processed by the `'path'` handler instead of the `'url'` one.
	 * @return string Rendered HTML
	 */
	protected function _render($method, $string, $params, array $options = []) {
		$strings = $this->_strings;

		if (isset($params['options']['scope'])) {
			$options['scope'] = $params['options']['scope'];
			unset($params['options']['scope']);
		}

		if ($this->_context) {
			foreach ($params as $key => $value) {
				$handler = isset($options['handlers'][$key]) ? $options['handlers'][$key] : $key;
				$params[$key] = $this->_context->applyHandler(
					$this, $method, $handler, $value, $options
				);
			}
			$strings = $this->_context->strings();
		}
		return Text::insert(isset($strings[$string]) ? $strings[$string] : $string, $params);
	}
}

?>