<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template;

use \RuntimeException;
use \lithium\core\Libraries;

class View extends \lithium\core\Object {

	/**
	 * Output filters for view rendering.
	 *
	 * @var array List of filters.
	 */
	public $outputFilters = array();

	/**
	 * Holds the details of the current request that originated the call to this view, if
	 * applicable.  May be empty if this does not apply.  For example, if the View class is
	 * created to render an email.
	 *
	 * @var object Request object instance.
	 * @see lithium\action\Request
	 */
	protected $_request = null;

	/**
	 * The object responsible for loading template files.
	 *
	 * @var object Loader object.
	 */
	protected $_loader = null;

	/**
	 * Object responsible for rendering output.
	 *
	 * @var objet Renderer object.
	 */
	protected $_renderer = null;

	/**
	 * Auto-configuration parameters.
	 *
	 * @var array Objects to auto-configure.
	 */
	protected $_autoConfig = array('request');

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration parameters.
	 *        Defaults are:
	 *          - `loader`: File
	 *          - `renderer`: File
	 *          - `request`: none specified
	 *          - `vars`: empty
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'request' => null,
			'vars' => array(),
			'loader' => 'File',
			'renderer' => 'File',
			'outputFilters' => array()
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Perform initialization of the View.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		foreach (array('loader', 'renderer') as $key) {
			if (is_object($this->_config[$key])) {
				$this->{'_' . $key} = $this->_config[$key];
				continue;
			}

			if (!$class = Libraries::locate('adapter.template.view', $this->_config[$key])) {
				throw new RuntimeException("Template adapter {$this->_config[$key]} not found");
			}
			$this->{'_' . $key} = new $class(array('view' => $this) + $this->_config);
		}

		$h = function($data) { return htmlspecialchars((string) $data); };
		$this->outputFilters += compact('h') + $this->_config['outputFilters'];
	}

	/**
	 * Render the view.
	 *
	 * @param string|array $type
	 * @param array $data
	 * @param array $options
	 * @return string The rendered view that was requested.
	 */
	public function render($type, array $data = array(), array $options = array()) {
		$defaults = array('context' => array(), 'type' => 'html', 'layout' => null);
		$options += $defaults;
		$template = null;

		if (is_array($type)) {
			list($type, $template) = each($type);
		}
		return $this->{"_" . $type}($template, $data, $options);
	}

	/**
	 * The 'all' render type handler.
	 *
	 * @param string $template Not used in this handler. Can be specified as null.
	 * @param array $data Template data.
	 * @param array $options Layout rendering options.
	 */
	protected function _all($template, $data, $options) {
		$content = $this->render('template', $data, $options);

		if (!$options['layout']) {
			return $content;
		}
		$options['context'] += array('content' => $content);
		return $this->_layout($template, $data, $options);
	}

	/**
	 * The 'element' render type handler.
	 *
	 * @param string $template Template to be rendered.
	 * @param array $data Template data.
	 * @param array $options Renderer options.
	 */
	protected function _element($template, $data, $options) {
		$options += array('controller' => 'elements', 'template' => $template);
		$template = $this->_loader->template('template', $options);
		$data = $data + $this->outputFilters;
		return $this->_renderer->render($template, $data, $options);
	}

	/**
	 * The 'template' render type handler.
	 *
	 * @param string $template Template to be rendered.
	 * @param array $data Template data.
	 * @param array $options Renderer options.
	 */
	protected function _template($template, $data, $options) {
		$template = $this->_loader->template('template', $options);
		$data = $data + $this->outputFilters;
		return $this->_renderer->render($template, $data, $options);
	}

	/**
	 * The 'layout' render type handler.
	 *
	 * @param string $template Not used in this handler.
	 * @param array $data Template data.
	 * @param array $options Renderer options.
	 */
	protected function _layout($template, $data, $options) {
		$template = $this->_loader->template('layout', $options);
		$data = (array) $data + $this->outputFilters;
		return $this->_renderer->render($template, $data, $options);
	}
}

?>