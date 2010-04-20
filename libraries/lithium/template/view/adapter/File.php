<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template\view\adapter;

use \Exception;
use \lithium\util\String;
use \lithium\core\Libraries;

/**
 * The File adapter implements both template loading and rendering, and uses the `view\Stream` class
 * to auto-escape template output with short tags (i.e. <?=).
 *
 * For more information about implementing your own template loaders or renderers, see the
 * `lithium\template\View` class.
 *
 * @see lithium\template\View
 * @see lithium\template\view\Compiler
 */
class File extends \lithium\template\view\Renderer implements \ArrayAccess {

	protected $_autoConfig = array(
		'classes' => 'merge', 'request', 'context', 'strings', 'handlers', 'view', 'compile'
	);

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
	protected $_data = array();

	protected $_classes = array(
		'compiler' => '\lithium\template\view\Compiler',
		'router' => 'lithium\net\http\Router',
		'media'  => 'lithium\net\http\Media'
	);

	public function __construct(array $config = array()) {
		$defaults = array('classes' => array(), 'compile' => true, 'extract' => true);
		parent::__construct($config + $defaults);
	}

	/**
	 * Renders content from a template file provided by `template()`.
	 *
	 * @param string $template
	 * @param string $data
	 * @param array $options
	 * @return string
	 */
	public function render($template, $data = array(), array $options = array()) {
		$defaults = array('context' => array());
		$options += $defaults;

		$this->_context = $options['context'] + $this->_context;
		$this->_data = (array) $data;
		$template__ = $template;
		unset($options, $template, $defaults);

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
	 * @param array $options
	 * @return string
	 */
	public function template($type, $options) {
		if (!isset($this->_config['paths'][$type])) {
			return null;
		}
		$options = array_filter($options, function($item) { return is_string($item); });

		if (isset($options['plugin'])) {
			$options['library'] = $options['plugin'];
		}

		$library = Libraries::get(isset($options['library']) ? $options['library'] : true);
		$options['library'] = $library['path'];
		$path = $this->_paths((array) $this->_config['paths'][$type], $options);

		if ($this->_compile) {
			$compiler = $this->_classes['compiler'];
			$path = $compiler::template($path);
		}
		return $path;
	}

	/**
	 * Allows checking to see if a value is set in template data, i.e. `$this['foo']` in templates.
	 *
	 * @param string $offset The key / variable name to check.
	 * @return boolean Returns `true` if the value is set, otherwise `false`.
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->_data);
	}

	public function offsetGet($offset) {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		$this->_data[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unset($this->_data[$offset]);
	}

	/**
	 * Searches a series of path templates for a matching template file, and returns the file name.
	 *
	 * @param array $paths The array of path templates to search.
	 * @param array $options The set of options keys to be interpolated into the path templates
	 *              when searching for the correct file to load.
	 * @return string Returns the first template file found. Throws an exception if no templates
	 *         are available.
	 */
	protected function _paths($paths, $options) {
		foreach ($paths as $path) {
			if (file_exists($path = String::insert($path, $options))) {
				return $path;
			}
		}
		throw new Exception("Template not found at {$path}");
	}
}

?>