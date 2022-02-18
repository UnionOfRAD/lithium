<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\template\view\adapter;

use lithium\util\Text;
use lithium\core\Libraries;
use lithium\template\TemplateException;

/**
 * The File adapter implements both template loading and rendering, and uses the
 * `lithium\template\view\Stream` class or `lithium\template\view\Compiler` class to auto-escape
 * template output with short tags (i.e. `<?=`).
 *
 * For more information about implementing your own template loaders or renderers, see the
 * `lithium\template\View` class.
 *
 * @see lithium\template\View
 * @see lithium\template\view\Compiler
 */
class File extends \lithium\template\view\Renderer implements \ArrayAccess {

	/**
	 * These configuration variables will automatically be assigned to their corresponding protected
	 * properties when the object is initialized.
	 *
	 * @var array
	 */
	protected $_autoConfig = [
		'classes' => 'merge', 'request', 'response', 'context',
		'strings', 'handlers', 'view', 'compile', 'paths'
	];

	/**
	 * Boolean flag indicating whether templates should be pre-compiled before inclusion. For more
	 * information on template compilation, see `view\Compiler`.
	 *
	 * @see lithium\template\view\Compiler
	 * @var boolean
	 */
	protected $_compile = true;

	/**
	 * An array containing the variables currently in the scope of the template. These values are
	 * manipulable using array syntax against the template object, i.e. `$this['foo'] = 'bar'`
	 * inside your template files.
	 *
	 * @var array
	 */
	protected $_data = [];

	/**
	 * Variables that have been set from a view/element/layout/etc. that should be available to the
	 * same rendering context.
	 *
	 * @var array Key/value pairs of variables
	 */
	protected $_vars = [];

	protected $_paths = [];

	/**
	 * `File`'s dependencies. These classes are used by the output handlers to generate URLs
	 * for dynamic resources and static assets, as well as compiling the templates.
	 *
	 * @see Renderer::$_handlers
	 * @var array
	 */
	protected $_classes = [
		'compiler' => 'lithium\template\view\Compiler',
		'router' => 'lithium\net\http\Router',
		'media'  => 'lithium\net\http\Media'
	];

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration options.
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'classes' => [],
			'compile' => true,
			'compiler' => [],
			'extract' => true,
			'paths' => []
		];
		parent::__construct($config + $defaults);
	}

	/**
	 * Renders content from a template file provided by `template()`.
	 *
	 * @param string $template
	 * @param array|string $data
	 * @param array $options
	 * @return string
	 */
	public function render($template, $data = [], array $options = []) {
		$defaults = ['context' => []];
		$options += $defaults;

		$this->_context = $options['context'] + $this->_context;
		$this->_data = (array) $data + $this->_vars;
		$this->_options = $options;
		$template__ = $template;
		unset($options, $template, $defaults, $data);

		if ($this->_config['extract']) {
			extract($this->_data, EXTR_OVERWRITE);
		} elseif ($this->_view) {
			extract((array) $this->_view->outputFilters, EXTR_OVERWRITE);
		}

		ob_start();
		include $template__;
		return ob_get_clean();
	}

	/**
	 * Returns a template file name
	 *
	 * @param string $type
	 * @param array $params
	 * @return string
	 */
	public function template($type, array $params) {
		$library = Libraries::get(isset($params['library']) ? $params['library'] : true);
		$params['library'] = $library['path'];
		$path = $this->_paths($type, $params);

		if ($this->_compile) {
			$compiler = $this->_classes['compiler'];
			$path = $compiler::template($path, $this->_config['compiler']);
		}
		return $path;
	}

	/**
	 * Allows checking to see if a value is set in template data.
	 *
	 * Part of `ArrayAccess`.
	 *
	 * ```
	 * isset($file['bar']);
	 * $file->offsetExists('bar');
	 * ```
	 *
	 * @param  string  $offset Key / variable name to check.
	 * @return boolean Returns `true` if the value is set, otherwise `false`.
	 */
	public function offsetExists($offset): bool {
		return array_key_exists($offset, $this->_data);
	}

	/**
	 * Gets the offset, or null in the template data.
	 *
	 * Part of `ArrayAccess`.
	 *
	 * ```
	 * $file['bar'];
	 * $file->offsetGet('bar');
	 * ```
	 *
	 * @param  string $offset Key / variable name to check.
	 * @return mixed
	 */
	public function offsetGet($offset): mixed {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}

	/**
	 * Sets the offset with the given value.
	 *
	 * Part of `ArrayAccess`.
	 *
	 * ```
	 * $file['bar'] = 'baz';
	 * $file->offsetSet('bar', 'baz');
	 * ```
	 *
	 * @param  string $offset Key / variable name to check.
	 * @param  mixed  $value  Value you wish to set to `$offset`.
	 * @return void
	 */
	public function offsetSet($offset, $value): void {
		$this->_data[$offset] = $value;
	}

	/**
	 * Unsets the given offset.
	 *
	 * Part of `ArrayAccess`.
	 *
	 * ```
	 * unset($file['bar']);
	 * $file->offsetUnset('bar');
	 * ```
	 *
	 * @param  string $offset Key / variable name to check.
	 * @return void
	 */
	public function offsetUnset($offset): void {
		unset($this->_data[$offset]);
	}

	/**
	 * Searches one or more path templates for a matching template file, and returns the file name.
	 *
	 * @param string $type
	 * @param array $params The set of options keys to be interpolated into the path templates
	 *              when searching for the correct file to load.
	 * @return string Returns the first template file found. Throws an exception if no templates
	 *         are available.
	 */
	protected function _paths($type, array $params) {
		if (!isset($this->_paths[$type])) {
			throw new TemplateException("Invalid template type '{$type}'.");
		}

		foreach ((array) $this->_paths[$type] as $path) {
			if (!file_exists($path = Text::insert($path, $params))) {
				continue;
			}
			return $path;
		}
		throw new TemplateException("Template not found at path `{$path}`.");
	}
}

?>