<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template\helper;

use \lithium\util\Set;
use \lithium\util\Inflector;
use \UnexpectedValueException;

/**
 * A helper class to facilitate generating, processing and securing HTML forms. By default, `Form`
 * will simply generate HTML forms and widgets, but by creating a form with a _binding object_,
 * the helper can pre-fill form input values, render error messages, and introspect column types.
 *
 * For example, assuming you have created a `Post` model in your application:
 * {{{// In controller code:
 * use \app\models\Post;
 * $post = Post::find(1);
 * return compact('post');
 *
 * // In view code:
 * <?=$this->form->create($post); // Echoes a <form> tag and binds the helper to $post ?>
 * <?=$this->form->text('title'); // Echoes an <input /> element, pre-filled with $post's title ?>
 * <?=$this->form->submit('Update'); // Echoes a submit button with the title 'Update' ?>
 * <?=$this->form->end(); // Echoes a </form> tag & unbinds the form ?>
 * }}}
 */
class Form extends \lithium\template\Helper {

	/**
	 * String templates used by this helper.
	 *
	 * @var array
	 */
	protected $_strings = array(
		'button'               => '<input type="{:type}"{:options} />',
		'checkbox'             => '<input type="checkbox" name="{:name}"{:options} />',
		'checkbox-multi'       => '<input type="checkbox" name="{:name}[]"{:options} />',
		'checkbox-multi-end'   => '',
		'checkbox-multi-start' => '',
		'error'                => '<div{:options}>{:content}</div>',
		'errors'               => '{:content}',
		'element'              => '<input type="{:type}" name="{:name}"{:options} />',
		'file'                 => '<input type="file" name="{:name}"{:options} />',
		'form'                 => '<form action="{:url}"{:options}>{:append}',
		'form-end'             => '</form>',
		'hidden'               => '<input type="hidden" name="{:name}"{:options} />',
		'field'                => '<div{:wrap}>{:label}{:input}{:error}</div>',
		'field-checkbox'       => '<div{:wrap}>{:input}{:label}{:error}</div>',
		'label'                => '<label for="{:name}"{:options}>{:title}</label>',
		'legend'               => '<legend>{:content}</legend>',
		'option-group'         => '<optgroup label="{:label}"{:options}>',
		'option-group-end'     => '</optgroup>',
		'password'             => '<input type="password" name="{:name}"{:options} />',
		'radio'         => '<input type="radio" name="{:name}" id="{:id}"{:options} />{:label}',
		'select-start'         => '<select name="{:name}"{:options}>',
		'select-multi-start'   => '<select name="{:name}[]"{:options}>',
		'select-empty'         => '<option value=""{:options}>&nbsp;</option>',
		'select-option'        => '<option value="{:value}"{:options}>{:title}</option>',
		'select-end'           => '</select>',
		'submit'               => '<input type="submit" value="{:title}"{:options} />',
		'submit-image'         => '<input type="image" src="{:url}"{:options} />',
		'text'                 => '<input type="text" name="{:name}"{:options} />',
		'textarea'             => '<textarea name="{:name}"{:options}>{:value}</textarea>',
		'fieldset'             => '<fieldset{:options}>{:content}</fieldset>',
		'fieldset-start'       => '<fieldset><legend>{:content}</legend>',
		'fieldset-end'         => '</fieldset>'
	);

	/**
	 * Maps method names to template string names, allowing the default template strings to be set
	 * permanently on a per-method basis.
	 *
	 * For example, if all text input fields should be wrapped in `<span />` tags, you can configure
	 * the template string mappings per the following:
	 *
	 * {{{
	 * $this->form->config(array('templates' => array(
	 * 	'text' => '<span><input type="text" name="{:name}"{:options} /></span>'
	 * )));
	 * }}}
	 *
	 * Alternatively, you can re-map one type as another. This is useful if, for example, you
	 * include your own helper with custom form template strings which do not match the default
	 * template string names.
	 *
	 * {{{
	 * // Renders all password fields as text fields
	 * $this->form->config(array('templates' => array('password' => 'text')));
	 * }}}
	 *
	 * @var array
	 * @see lithium\template\helper\Form::config()
	 */
	protected $_templateMap = array(
		'create' => 'form',
		'end' => 'form-end'
	);

	/**
	 * The data object or list of data objects to which the current form is bound. In order to
	 * be a custom data object, a class must implement the following methods:
	 *
	 * - schema(): Returns an array defining the objects fields and their data types.
	 * - data(): Returns an associative array of the data that this object represents.
	 * - errors(): Returns an associatie array of validation errors for the current data set, where
	 *             the keys match keys from `schema()`, and the values are either strings (in cases
	 *             where a field only has one error) or an array (in case of multiple errors),
	 *
	 * For an example of how to implement these methods, see the `lithium\data\model\Record` object.
	 *
	 * @var mixed A single data object, a `Collection` of multiple data ovjects, or an array of data
	 *            objects/`Collection`s.
	 * @see lithium\data\model\Record
	 * @see lithium\template\helper\Form::create()
	 */
	protected $_binding = null;

	public function __construct(array $config = array()) {
		$defaults = array(
			'base' => array(), 'text' => array(), 'textarea' => array(),
			'select' => array('multiple' => false)
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Object initializer. Adds a content handler for the `wrap` key in the `field()` method, which
	 * converts an array of properties to an attribute string.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		$this->_context->handlers(array('wrap' => '_attributes'));
	}

	/**
	 * Allows you to configure a default set of options which are included on a per-method basis,
	 * and configure method template overrides.
	 *
	 * To force all `<label />` elements to have a default `class` attribute value of `"foo"`,
	 * simply do the following:
	 *
	 * {{{
	 * $this->form->config(array('label' => array('class' => 'foo')));
	 * }}}
	 *
	 * @see lithium\template\helper\Form::$_templateMap
	 * @param array $config An associative array where the keys are `Form` method names, and the
	 *              values are arrays of configuration options to be included in the `$options`
	 *              parameter of each method specified.
	 * @return array Returns an array containing the currently set per-method configurations, and
	 *         an array of the currently set template overrides (in the `'templates'` array key).
	 */
	public function config(array $config = array()) {
		if (empty($config)) {
			return array('templates' => $this->_templateMap) + array_intersect_key(
				$this->_config, array('base' => '', 'text' => '', 'textarea' => '')
			);
		}
		if (isset($config['templates'])) {
			$this->_templateMap = $config['templates'] + $this->_templateMap;
			unset($config['templates']);
		}
		return ($this->_config = Set::merge($this->_config, $config)) + array(
			'templates' => $this->_templateMap
		);
	}

	/**
	 * Creates an HTML form, and optionally binds it to a data object which contains information on
	 * how to render form fields, any data to pre-populate the form with, and any validation errors.
	 * Typically, a data object will be a `Record` object returned from a `Model`, but you can
	 * define your own custom objects as well. For more information on custom data objects, see
	 * `lithium\template\helper\Form::$_binding`.
	 *
	 * @see lithium\template\helper\Form::$_binding
	 * @see lithium\data\model\Record
	 * @param object $binding
	 * @param array $options
	 * @return string Returns a `<form />` open tag with the `action` attribute defined by either
	 *         the `'action'` or `'url'` options (defaulting to the current page if none is
	 *         specified), the HTTP method is defined by the `'method'` option, and any HTML
	 *         attributes passed in `$options`.
	 */
	public function create($binding = null, array $options = array()) {
		if ($binding) {
			foreach (array('data', 'errors', 'exists') as $method) {
				if (!method_exists($binding, $method)) {
					throw new UnexpectedValueException("Invalid binding object passed.");
				}
			}
		}

		$defaults = array(
			'url' => $this->_context->request()->params,
			'type' => null,
			'action' => null,
			'method' => $binding ? ($binding->exists() ? 'put' : 'post') : 'post'
		);
		list(, $options, $template) = $this->_defaults(__FUNCTION__, null, $options);
		list($scope, $options) = $this->_options($defaults, $options);

		$_binding =& $this->_binding;
		$method = __METHOD__;
		$params = compact('scope', 'options', 'binding');

		$filter = function($self, $params, $chain) use ($method, $template, $defaults, &$_binding) {
			$scope = $params['scope'];
			$options = $params['options'];
			$_binding = $params['binding'];
			$append = null;

			if ($scope['type'] == 'file') {
				if (strtolower($scope['method']) == 'get') {
					$scope['method'] = 'post';
				}
				$options['enctype'] = 'multipart/form-data';
			}

			if (!in_array(strtolower($scope['method']), array('get', 'post'))) {
				$append = $self->hidden('_method', array(
					'value' => strtoupper($scope['method'])
				));
				$scope['method'] = 'post';
			}

			$url = $scope['action'] ? array('action' => $scope['action']) : $scope['url'];
			$options['method'] = strtolower($scope['method']);

			return $self->invokeMethod('_render', array(
				$method, $template, compact('url', 'options', 'append')
			));
		};
		return $this->_filter($method, $params, $filter);
	}

	/**
	 * Echoes a closing `</form>` tag and unbinds the `Form` helper from any `Record` or `Document`
	 * object used to generate the corresponding form.
	 *
	 * @return string Returns a closing `</form>` tag.
	 */
	public function end() {
		list(, $options, $template) = $this->_defaults(__FUNCTION__, null, array());
		$params = compact('options', 'template');
		$_binding =& $this->_binding;
		$_context =& $this->_context;

		$filter = function($self, $params, $chain) use (&$_binding, &$_context) {
			unset($_binding);
			return $_context->strings('form-end');
		};
		$result = $this->_filter(__METHOD__, $params, $filter);
		unset($this->_binding);
		$this->_binding = null;
		return $result;
	}

	/**
	 * Implements alternative input types as method calls against `Form` helper. Enables the
	 * generation of HTML5 input types and other custom input types:
	 *
	 * {{{ embed:lithium\tests\cases\template\helper\FormTest::testCustomInputTypes(1-2) }}}
	 *
	 * @param string $type The method called, which represents the `type` attribute of the
	 *               `<input />` tag.
	 * @param array $params An array of method parameters passed to the method call. The first
	 *              element should be the name of the input field, and the second should be an array
	 *              of element attributes.
	 * @return string Returns an `<input />` tag of the type specified in `$type`.
	 */
	public function __call($type, array $params = array()) {
		$params += array(null, array());
		list($name, $options) = $params;
		list($name, $options, $template) = $this->_defaults($type, $name, $options);
		$template = $this->_context->strings($template) ? $template : 'element';
		return $this->_render($type, $template, compact('type', 'name', 'options', 'value'));
	}

	/**
	 * Generates a form field with a label, input, and error message (if applicable), all contained
	 * within a wrapping element.
	 *
	 * @param string $name The name of the field to render. If the form was bound to an object
	 *               passed in `create()`, `$name` should be the field name of a
	 * @param array $options Rendering options for the form field.
	 * @return string Returns a form input (the input type is based on the `'type'` option), with
	 *         label and error message, wrapped in a `<div />` element.
	 */
	public function field($name, array $options = array()) {
		$defaults = array(
			'label' => null,
			'type' => 'text',
			'template' => 'field',
			'wrap' => array(),
			'list' => null
		);
		list($options, $fieldOptions) = $this->_options($defaults, $options);
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);

		if ($options['template'] != $defaults['template']) {
			$template = $options['template'];
		}
		$wrap = $options['wrap'];
		$type = $options['type'];
		$label = $input = null;

		if ($options['label'] === null || $options['label']) {
			$label = $this->label($name, $options['label']);
		}

		switch (true) {
			case ($type == 'select'):
				$input = $this->select($name, $options['list'], $fieldOptions);
			break;
			default:
				$input = $this->{$type}($name, $fieldOptions);
			break;
		}
		$error = ($this->_binding) ? $this->error($name) : null;
		$params = compact('wrap', 'label', 'input', 'error');

		return $this->_render(__METHOD__, $template, $params);
	}

	/**
	 * Generates an HTML `<input type="submit" />` object.
	 *
	 * @param string $title The title of the submit button.
	 * @param array $options
	 * @return string Returns a submit `<input />` tag with the given title and HTML attributes.
	 */
	public function submit($title = null, array $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, null, $options);
		return $this->_render(__METHOD__, $template, compact('title', 'options'));
	}

	/**
	 * Generates an HTML `<textarea></textarea>` object.
	 *
	 * @param string $name The name of the field.
	 * @param array $options
	 * @return string Returns a `<textarea>` tag with the given name and HTML attributes.
	 */
	public function textarea($name, array $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		list($scope, $options) = $this->_options(array('value' => null), $options);
		$value = isset($scope['value']) ? $scope['value'] : '';
		return $this->_render(__METHOD__, $template, compact('name', 'options', 'value'));
	}

	/**
	 * Generates an HTML `<input type="text" />` object.
	 *
	 * @param string $name The name of the field.
	 * @param array $options
	 * @return string Returns a `<input />` tag with the given name and HTML attributes.
	 */
	public function text($name, array $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	/**
	 * Generates a `<select />` list using the `$list` parameter for the `<option />` tags. The
	 * default selection will be set to the value of `$options['value']`, if specified.
	 *
	 * For example: {{{
	 * $this->form->select('colors', array(1 => 'red', 2 => 'green', 3 => 'blue'), array(
	 * 	'id' => 'Colors', 'value' => 2
	 * ));
	 * // Renders a '<select />' list with options 'red', 'green' and 'blue', with the 'green'
	 * // option as the selection
	 * }}}
	 *
	 * @param string $name The `name` attribute of the `<select />` element.
	 * @param array $list An associative array of key/value pairs, which will be used to render the
	 *              list of options.
	 * @param array $options Any HTML attributes that should be associated with the `<select />`
	 *             element. If the `'value'` key is set, this will be the value of the option
	 *             that is selected by default.
	 * @return string Returns an HTML `<select />` element.
	 */
	public function select($name, $list = array(), array $options = array()) {
		$defaults = array('empty' => false, 'value' => null);
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		list($scope, $options) = $this->_options($defaults, $options);

		if ($scope['empty']) {
			$list = array('' => ($scope['empty'] === true) ? '' : $scope['empty']) + $list;
		}
		$startTemplate = ($scope['multiple']) ? 'select-multi-start' : 'select-start';
		$output = $this->_render(__METHOD__, $startTemplate, compact('name', 'options'));

		foreach ($list as $value => $title) {
			$selected = false;

			if (is_array($scope['value']) && in_array($value, $scope['value'])) {
				$selected = true;
			} elseif ($scope['value'] == $value) {
				$selected = true;
			}
			$options = $selected ? array('selected' => true) : array();

			$output .= $this->_render(__METHOD__, 'select-option', compact(
				'value', 'title', 'options'
			));
		}
		return $output . $this->_context->strings('select-end');
	}

	/**
	 * Generates an HTML `<input type="checkbox" />` object.
	 *
	 * @param string $name The name of the field.
	 * @param array $options
	 * @return string Returns a `<input />` tag with the given name and HTML attributes.
	 */
	public function checkbox($name, array $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		list($scope, $options) = $this->_options(array('value' => null), $options);

		if (!isset($scope['checked'])) {
			$options['checked'] = isset($scope['value']) ? $scope['value'] : false;
		}
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	/**
	 * Generates an HTML `<input type="password" />` object.
	 *
	 * @param string $name The name of the field.
	 * @param array $options
	 * @return string Returns a `<input />` tag with the given name and HTML attributes.
	 */
	public function password($name, array $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	/**
	 * Generates an HTML `<input type="hidden" />` object.
	 *
	 * @param string $name The name of the field.
	 * @param array $options
	 * @return string Returns a `<input />` tag with the given name and HTML attributes.
	 */
	public function hidden($name, array $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	/**
	 * Generates an HTML `<label></label>` object.
	 *
	 * @param string $name The name of the field that the label is for.
	 * @param string $title The content inside the `<label></label>` object.
	 * @param array $options
	 * @return string Returns a `<label>` tag for the name and with HTML attributes.
	 */
	public function label($name, $title = null, array $options = array()) {
		$defaults = array('escape' => true);

		if (is_array($title)) {
			list($title, $options) = each($title);
		}
		$title = $title ?: Inflector::humanize($name);

		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		list($scope, $options) = $this->_options($defaults, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'title', 'options'), $scope);
	}

	/**
	 * Generates an error message for a field which is part of an object bound to a form in
	 * `create()`.
	 *
	 * @param string $name The name of the field for which to render an error.
	 * @param mixed $key If more than one error is present for `$name`, a key may be specified.
	 *              By default, the first available error is used.
	 * @param array $options Any rendering options or HTML attributes to be used when rendering
	 *              the error.
	 * @return string Returns a rendered error message based on the `'error'` string template.
	 */
	public function error($name, $key = null, array $options = array()) {
		$defaults = array('class' => 'error');
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		$options += $defaults;

		if (!$this->_binding || !$content = $this->_binding->errors($name)) {
			return null;
		}
		if (is_array($content)) {
			$content = !isset($content[$key]) ? reset($content) : $content[$key];
		}
		return $this->_render(__METHOD__, $template, compact('content', 'options'));
	}

	/**
	 * Builds the defaults array for a method by name, according to the config.
	 *
	 * @param string $method The name of the method to create defaults for.
	 * @param string $name The `$name` supplied to the original method.
	 * @param string $options `$options` from the original method.
	 * @return array Defaults array contents.
	 */
	protected function _defaults($method, $name, $options) {
		$methodConfig = isset($this->_config[$method]) ? $this->_config[$method] : array();
		$options += $methodConfig + $this->_config['base'];

		$hasValue = (
			(!isset($options['value']) || empty($options['value'])) &&
			$name && $this->_binding && $value = $this->_binding->data($name)
		);
		if ($hasValue) {
			$options['value'] = $value;
		}
		if (isset($options['default']) && empty($options['value'])) {
			$options['value'] = $options['default'];
		}
		unset($options['default']);
		$template = isset($this->_templateMap[$method]) ? $this->_templateMap[$method] : $method;
		return array($name, $options, $template);
	}
}

?>
