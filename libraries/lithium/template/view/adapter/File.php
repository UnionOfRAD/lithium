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
 * @see lithium\template\view\Stream
 */
class File extends \lithium\template\view\Renderer {

	protected $_autoConfig = array(
		'classes' => 'merge', 'request', 'context', 'strings', 'handlers', 'view'
	);

	protected $_classes = array(
		'stream' => '\lithium\template\view\Stream',
		'router' => 'lithium\net\http\Router',
		'media'  => 'lithium\net\http\Media'
	);

	public function __construct($config = array()) {
		$defaults = array('protocol' => 'lithium.template', 'classes' => array());
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
	public function render($template, $data = array(), $options = array()) {
		$this->_context = $options['context'] + $this->_context;

		$__t__ = $this->_config['protocol'] . '://' . $template;
		unset($options);
		extract($data, EXTR_OVERWRITE);
		ob_start();
		include $__t__;
		return ob_get_clean();
	}

	/**
	 * Returns a template file name
	 *
	 * @param string $type
	 * @param string $options
	 * @return void
	 * @todo Replace me with include_path search?
	 */
	public function template($type, $options) {
		if (!isset($this->_config['paths'][$type])) {
			return null;
		}
		$options = array_filter($options, function($item) { return is_string($item); });

		if (isset($options['plugin'])) {
			$options['library'] = $options['plugin'];
		}

		$options['library'] = isset($options['library']) ? $options['library'] : 'app';
		$library = Libraries::get($options['library']);
		$options['library'] = $library['path'];

		foreach ((array) $this->_config['paths'][$type] as $path) {
			if (file_exists($path = String::insert($path, $options))) {
				return $path;
			}
		}
		throw new Exception("Template not found at {$path}");
	}

	protected function _init() {
		parent::_init();

		if (!in_array($this->_config['protocol'], stream_get_wrappers())) {
			stream_wrapper_register($this->_config['protocol'], $this->_classes['stream']);
		}
	}
}

?>