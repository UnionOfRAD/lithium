<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template;

use RuntimeException;
use lithium\core\Libraries;

/**
 * As one of the three pillars of the Model-View-Controller design pattern, the `View` class
 * (along with other supporting classes) is responsible for taking the data passed from the
 * request and/or controller, inserting this into the requested view/layout, and then presenting
 * the rendered content in the appropriate content-type.
 *
 * The `View` class interacts with a variety of other classes in order to achieve maximum
 * flexibility and configurability at all points in the view rendering and presentation
 * process. The `Loader` class is tasked with locating and reading template files which are then
 * passed to the `Renderer` adapter subclass.
 *
 * It is also possible to instantiate and call `View` directly, in cases where you wish to bypass
 * all other parts of the framework and simply return rendered content.
 *
 * A simple example, using the `Simple` renderer/loader for string templates:
 *
 * {{{
 * $view = new View(array('loader' => 'Simple', 'renderer' => 'Simple'));
 * echo $view->render(array('element' => 'Hello, {:name}!'), array(
 *     'name' => "Robert"
 * ));
 *
 * // Output:
 * "Hello, Robert!";
 * }}}
 *
 * (note: This is easily adapted for XML templating).
 *
 * Another example, this time of something that could be used in an appliation
 * error handler:
 *
 * {{{
 * $view = new View(array(
 *     'paths' => array(
 *         'template' => '{:library}/views/errors/{:template}.{:type}.php',
 *         'layout'   => '{:library}/views/layouts/{:layout}.{:type}.php',
 *     )
 * ));
 *
 * echo $View->render('all', array('content' => $info), array(
 *     'template' => '404',
 *     'type' => 'html',
 *     'layout' => 'error'
 * ));
 * }}}
 *
 * @see lithium\view\Renderer
 * @see lithium\view\adapter
 * @see lithium\net\http\Media
 */
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
	 * @see lithium\action\Request
	 * @var object `Request` object instance.
	 */
	protected $_request = null;

	/**
	 * Holds a reference to the `Response` object that will be returned at the end of the current
	 * dispatch cycle. Allows headers and other response attributes to be assigned in the templating
	 * layer.
	 *
	 * @see lithium\action\Response
	 * @var object `Response` object instance.
	 */
	protected $_response = null;

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
	protected $_autoConfig = array('request', 'response');

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration parameters.
	 *        The available options are:
	 *          - `loader`: For locating/reading view, layout and element
	 *                      templates. Defaults to `File`.
	 *          - `renderer`: Populates the view/layout with the data set from the controller.
	 *                        Defaults to `File`.
	 *          - `request`: The request object to be made available in the view. Defalts to `null`.
	 *          - `vars`: Defaults to `array()`.
	 * @return void
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'request' => null,
			'response' => null,
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
	 * @throws RuntimeException when template adapter cannot be found.
	 */
	protected function _init() {
		parent::_init();

		foreach (array('loader', 'renderer') as $key) {
			if (is_object($this->_config[$key])) {
				$this->{'_' . $key} = $this->_config[$key];
				continue;
			}
			$class = $this->_config[$key];
			$config = array('view' => $this) + $this->_config;
			$this->{'_' . $key} = Libraries::instance('adapter.template.view', $class, $config);
		}
		$encoding = 'UTF-8';

		if ($this->_response) {
			$encoding =& $this->_response->encoding;
		}

		$h = function($data) use (&$encoding) {
			return htmlspecialchars((string) $data, ENT_QUOTES, $encoding);
		};
		$this->outputFilters += compact('h') + $this->_config['outputFilters'];
	}

	/**
	 * Render a layout, template, view or element.
	 *
	 * @param string|array $type The view type. Possible values are `element`, `template`,
	 *        `layout` and `all`.
	 * @param array $data The data to be made available in the rendered view.
	 * @param array $options Rendering options:
	 *        - `context`: Render context
	 *        - `type`: The media type to render. Defaults to `html`.
	 *        - `layout`: The layout in which the rendered view should be wrapped in.
	 * @return string The rendered view that was requested.
	 */
	public function render($type, $data = null, array $options = array()) {
		$defaults = array('context' => array(), 'type' => 'html', 'layout' => null);
		$options += $defaults;

		$data = $data ?: array();
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
	protected function _all($template, $data, array $options = array()) {
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
	protected function _element($template, $data, array $options = array()) {
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
	protected function _template($template, $data, array $options = array()) {
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
	protected function _layout($template, $data, array $options = array()) {
		$template = $this->_loader->template('layout', $options);
		$data = (array) $data + $this->outputFilters;
		return $this->_renderer->render($template, $data, $options);
	}
}

?>