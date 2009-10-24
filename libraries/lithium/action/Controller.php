<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\action;

use \Exception;

/**
 * The `Controller` class is the fundamental building block of your application's request/response
 * cycle. Controllers are organized around a single logical entity, usually one or more model
 * classes (i.e. `lithium\data\Model`) and is tasked with performing operations against that entity.
 *
 * Each controller has a series of 'actions' which are defined as class methods of the `Controller`
 * classes. Each action has a specific responsibility, such as listing a set of objects, updating an
 * object, or deleting an object.
 *
 * A controller object is instatiated by the `Dispatcher` (`lithium\http\Dispatcher`), and is given
 * an instance of the `lithium\action\Request` class, which contains all necessary request state,
 * including routing information, `GET` & `POST` data, and server variables. The controller is then
 * invoked (using PHP's magic `__invoke()` syntax), and the proper action is called, according to
 * the routing information stored in the `Request` object.
 *
 * A controller then returns a response (i.e. using `redirect()` or `render()`) which includes HTTP
 * headers, and/or a serialized data response (JSON or XML, etc.) or HTML webpage.
 *
 * For more information on returning serialized data responses for web services, or manipulating
 * template rendering from within your controllers, see the settings in `$_render` and the
 * `lithium\http\Media` class.
 *
 * @see lithium\http\Media
 */
class Controller extends \lithium\core\Object {

	public $request = null;

	public $response = null;

	protected $_render = array(
		'type'        => 'html',
		'data'        => array(),
		'auto'        => true,
		'layout'      => 'default',
		'template'    => null,
		'hasRendered' => false
	);

	/**
	 * Lists `Controller`'s class dependencies. For details on extending or replacing a class,
	 * please refer to that class's API.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'media' => '\lithium\http\Media',
		'router' => '\lithium\http\Router',
		'response' => '\lithium\action\Response'
	);

	public function __construct($config = array()) {
		$defaults = array(
			'request' => null, 'response' => array(),
			'render' => array(), 'classes' => array()
		);
		$config += $defaults;

		if (!empty($config['request'])) {
			$this->request = $config['request'];
		}

		foreach (array('render', 'classes') as $key) {
			if (!empty($config[$key])) {
				$this->{'_' . $key} = (array)$config[$key] + $this->{'_' . $key};
			}
		}
		parent::__construct($config);
	}

	/**
	 * Called by the Dispatcher class to invoke an action.
	 *
	 * @param object $request The request object with URL and HTTP info for dispatching this action.
	 * @param array $dispatchParams The array of parameters that will be passed to the action.
	 * @param array $options The dispatch options for this action.
	 * @return object Returns the response object associated with this controller.
	 * @todo Implement proper exception catching/throwing
	 * @filter This method can be filtered.
	 */
	public function __invoke($request, $dispatchParams, $options = array()) {
		$classes = $this->_classes;
		$config = $this->_config;
		$render =& $this->_render;

		$filter = function($self, $params, $chain) use ($config, $classes, &$render) {
			extract($params, EXTR_OVERWRITE);
			$action = $dispatchParams['action'];
			$args = isset($dispatchParams['args']) ? $dispatchParams['args'] : array();
			$result = null;

			if (substr($action, 0, 1) == '_' || method_exists(__CLASS__, $action)) {
				throw new Exception('Private method!');
			}

			$response = $config['response'] + array('request' => $self->request);
			$self->response = new $classes['response']($response);
			$render['template'] = $render['template'] ?: $action;

			try {
				$result = $self->invokeMethod($action, $args);
			} catch (Exception $e) {
				// See todo, temporary alleviating obscure failure
				throw $e;
			}

			if (!empty($result)) {
				if (is_string($result)) {
					$self->render(array('text' => $result));
				} elseif (is_array($result)) {
					$self->set($result);
				}
			}

			if (!$render['hasRendered'] && $render['auto']) {
				$self->render($action);
			}
			return $self->response;
		};
		return $this->_filter(__METHOD__, compact('dispatchParams', 'request', 'options'), $filter);
	}

	public function set($data = array()) {
		$this->_render['data'] += (array)$data;
	}

	/**
	 * Uses results (typically coming from a controller action) to generate content and headers for
	 * a Response object.
	 *
	 * @param mixed $options A string template name (see the 'template' option below), or an array
	 *              of options, as follows:
	 *              - 'template': The name of a template, which usually matches the name of the
	 *                action. By default, this template is looked for in the views directory of the
	 *                current controller, i.e. given a `PostsController` object, if template is set
	 *                to `'view'`, the template path would be `views/posts/view.html.php`. Defaults
	 *                to the name of the action being rendered.
	 *              - 'head': If true, only renders the headers of the response, not the body.
	 *                Defaults to false.
	 *              - 'data': An associative array of variables to be assigned to the template.
	 *                These are merged on top of any variables set in `Controller::set()`.
	 * @return void
	 */
	public function render($options = array()) {
		if (is_string($options)) {
			$options = array('template' => $options);
		}
		$defaults = array(
			'status' => 200, 'location' => false,
			'data' => array(), 'head' => false,
		);
		$options += $defaults;
		$media = $this->_classes['media'];

		if (!empty($options['data'])) {
			$this->set($options['data']);
			unset($options['data']);
		}
		$options = $options + $this->_render + array('request' => $this->request);
		$type = key($options);
		$types = array_flip($media::types());

		if (isset($types[$type])) {
			$options['type'] = $type;
			$this->set(current($options));
			unset($options[$type]);
		}

		$this->_render['hasRendered'] = true;
		$this->response->type($options['type']);
		$this->response->status($options['status']);
		$this->response->headers('Location', $options['location']);

		if ($options['head']) {
			return;
		}
		$data = $this->_render['data'];
		$data = (isset($data[0]) && count($data) == 1) ? $data[0] : $data;
		$media::render($this->response, $data, $options);
	}

	/**
	 * Creates a redirect response.
	 *
	 * @param mixed $url
	 * @param array $options
	 * @return void
	 * @filter This method can be filtered.
	 */
	public function redirect($url, $options = array()) {
		$router = $this->_classes['router'];
		$defaults = array(
			'location' => $router::match($url, $this->request),
			'status' => 302,
			'head' => true,
			'exit' => true
		);
		$options += $defaults;

		$this->_filter(__METHOD__, compact('options'), function($self, $params, $chain) {
			$self->render($params['options']);
		});

		if ($options['exit']) {
			$this->response->render();
			$this->_stop();
		}
		return $this->response;
	}
}

?>