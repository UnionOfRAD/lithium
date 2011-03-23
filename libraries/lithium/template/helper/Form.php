<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template\helper;

use lithium\util\Set;
use lithium\util\Inflector;

/**
 * A helper class to facilitate generating, processing and securing HTML forms. By default, `Form`
 * will simply generate HTML forms and widgets, but by creating a form with a _binding object_,
 * the helper can pre-fill form input values, render error messages, and introspect column types.
 *
 * For example, assuming you have created a `Posts` model in your application:
 * {{{// In controller code:
 * use app\models\Posts;
 * $post = Posts::find(1);
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
		'field-radio'          => '<div{:wrap}>{:input}{:label}{:error}</div>',
		'label'                => '<label for="{:name}"{:options}>{:title}</label>',
		'legend'               => '<legend>{:content}</legend>',
		'option-group'         => '<optgroup label="{:label}"{:options}>',
		'option-group-end'     => '</optgroup>',
		'password'             => '<input type="password" name="{:name}"{:options} />',
		'radio'                => '<input type="radio" name="{:name}" {:options} />',
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
	 * For an example of how to implement these methods, see the `lithium\data\Entity` object.
	 *
	 * @see lithium\data\Entity
	 * @see lithium\data\Collection
	 * @see lithium\template\helper\Form::create()
	 * @var mixed A single data object, a `Collection` of multiple data objects, or an array of data
	 *            objects/`Collection`s.
	 */
	protected $_binding = null;

	/**
	 * Array of options used to create the form to which `$_binding` is currently bound.
	 * Overwritten when `end()` is called.
	 *
	 * @var array
	 */
	protected $_bindingOptions = array();

	public function __construct(array $config = array()) {
		$self =& $this;

		$defaults = array(
			'base' => array(),
			'text' => array(),
			'textarea' => array(),
			'select' => array('multiple' => false),
			'attributes' => array(
				'id' => function($method, $name, $options) use (&$self) {
					if (in_array($method, array('create', 'end', 'label', 'error'))) {
						return;
					}
					if (!$name || ($method == 'hidden' && $name == '_method')) {
						return;
					}
					$id = Inflector::camelize(Inflector::slug($name));
					$model = ($binding = $self->binding()) ? $binding->model() : null;
					return $model ? basename(str_replace('\\', '/', $model)) . $id : $id;
				}
			)
		);
		parent::__construct(Set::merge($defaults, $config));
	}

	/**
	 * Object initializer. Adds a content handler for the `wrap` key in the `field()` method, which
	 * converts an array of properties to an attribute string.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		if ($this->_context) {
			$this->_context->handlers(array('wrap' => '_attributes'));
		}
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
	 * Note that this can be overridden on a case-by-case basis, and when overridding, values are
	 * not merged or combined. Therefore, if you wanted a particular `<label />` to have both `foo`
	 * and `bar` as classes, you would have to specify `'class' => 'foo bar'`.
	 *
	 * You can also use this method to change the string template that a method uses to render its
	 * content. For example, the default template for rendering a checkbox is
	 * `'<input type="checkbox" name="{:name}"{:options} />'`. However, suppose you implemented your
	 * own custom UI elements, and you wanted to change the markup used, you could do the following:
	 *
	 * {{{
	 * $this->form->config(array('templates' => array(
	 * 	'checkbox' => '<div id="{:name}" class="ui-checkbox-element"{:options}></div>'
	 * )));
	 * }}}
	 *
	 * Now, for any calls to `$this->form->checkbox()`, your custom markup template will be applied.
	 * This works for any `Form` method that renders HTML elements.
	 *
	 * @see lithium\template\helper\Form::$_templateMap
	 * @param array $config An associative array where the keys are `Form` method names (or
	 *              `'templates'`, to include a template-overriding sub-array), and the
	 *              values are arrays of configuration options to be included in the `$options`
	 *              parameter of each method specified.
	 * @return array Returns an array containing the currently set per-method configurations, and
	 *         an array of the currently set template overrides (in the `'templates'` array key).
	 */
	public function config(array $config = array()) {
		if (!$config) {
			$keys = array('base' => '', 'text' => '', 'textarea' => '', 'attributes' => '');
			return array('templates' => $this->_templateMap) + array_intersect_key(
				$this->_config, $keys
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
	 * @see lithium\data\Entity
	 * @param object $binding The object to bind the form to. This is usually an instance of
	 *               `Record` or `Document`, or some other class that extends
	 *               `lithium\data\Entity`.
	 * @param array $options Other parameters for creating the form. Available options are:
	 *              - `'url'` _mixed_: A string URL or URL array parameters defining where in the
	 *                application the form should be submitted to.
	 *              - `'action'` _string_: This is a shortcut to be used if you wish to only
	 *                specify the name of the action to submit to, and use the default URL
	 *                parameters (i.e. the current controller, etc.) for generating the remainder
	 *                of the URL. Ignored if the `'url'` key is set.
	 *              - `'type'` _string_: Currently the only valid option is `'file'`. Set this if
	 *                the form will be used for file uploads.
	 *              - `'method'` _string_: Represents the HTTP method with which the form will be
	 *                submitted (`'get'`, `'post'`, `'put'` or `'delete'`). If `'put'` or
	 *                `'delete'`, the request method is simulated using a hidden input field.
	 * @return string Returns a `<form />` open tag with the `action` attribute defined by either
	 *         the `'action'` or `'url'` options (defaulting to the current page if none is
	 *         specified), the HTTP method is defined by the `'method'` option, and any HTML
	 *         attributes passed in `$options`.
	 * @filter
	 */
	public function create($binding = null, array $options = array()) {
		$request = $this->_context ? $this->_context->request() : null;

		$defaults = array(
			'url' => $request ? $request->params : array(),
			'type' => null,
			'action' => null,
			'method' => $binding ? ($binding->exists() ? 'put' : 'post') : 'post'
		);

		list(, $options, $tpl) = $this->_defaults(__FUNCTION__, null, $options);
		list($scope, $options) = $this->_options($defaults, $options);

		$_binding =& $this->_binding;
		$_options =& $this->_bindingOptions;
		$params = compact('scope', 'options', 'binding');
		$extra = array('method' => __METHOD__) + compact('tpl', 'defaults');

		$filter = function($self, $params) use ($extra, &$_binding, &$_options) {
			$scope = $params['scope'];
			$options = $params['options'];
			$_binding = $params['binding'];
			$append = null;
			$scope['method'] = strtolower($scope['method']);

			if ($scope['type'] == 'file') {
				if ($scope['method'] == 'get') {
					$scope['method'] = 'post';
				}
				$options['enctype'] = 'multipart/form-data';
			}

			if (!($scope['method'] == 'get' || $scope['method'] == 'post')) {
				$append = $self->hidden('_method', array('value' => strtoupper($scope['method'])));
				$scope['method'] = 'post';
			}

			$url = $scope['action'] ? array('action' => $scope['action']) : $scope['url'];
			$options['method'] = strtolower($scope['method']);
			$args = array($extra['method'], $extra['tpl'], compact('url', 'options', 'append'));
			$_options = $scope + $options;

			return $self->invokeMethod('_render', $args);
		};
		return $this->_filter(__METHOD__, $params, $filter);
	}

	/**
	 * Echoes a closing `</form>` tag and unbinds the `Form` helper from any `Record` or `Document`
	 * object used to generate the corresponding form.
	 *
	 * @return string Returns a closing `</form>` tag.
	 * @filter
	 */
	public function end() {
		list(, $options, $template) = $this->_defaults(__FUNCTION__, null, array());
		$params = compact('options', 'template');
		$_binding =& $this->_binding;
		$_context =& $this->_context;
		$_options =& $this->_bindingOptions;

		$filter = function($self, $params) use (&$_binding, &$_context, &$_options) {
			unset($_binding);
			$_options = array();
			return $_context->strings('form-end');
		};
		$result = $this->_filter(__METHOD__, $params, $filter);
		unset($this->_binding);
		$this->_binding = null;
		return $result;
	}

	/**
	 * Returns the entity that the `Form` helper is currently bound to.
	 *
	 * @see lithium\template\helper\Form::$_binding
	 * @return object Returns an object, usually an instance of `lithium\data\Entity`.
	 */
	public function binding() {
		return $this->_binding;
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
	 * {{{
	 *  echo $this->form->field('name');
	 *  echo $this->form->field('present', array('type' => 'checkbox'));
	 *  echo $this->form->field(array('email' => 'Enter a valid email'));
	 *  echo $this->form->field(array('name','email','phone'), array('div' => false));
	 * }}}
	 * @param mixed $name The name of the field to render. If the form was bound to an object
	 *                   passed in `create()`, `$name` should be the name of a field in that object.
	 *                   Otherwise, can be any arbitrary field name, as it will appear in POST data.
	 *                   Alternatively supply an array of fields that will use the same options
	 *                   array($field1 => $label1, $field2, $field3 => $label3)
	 * @param array $options Rendering options for the form field. The available options are as
	 *              follows:
	 *              - `'label'` _mixed_: A string or array defining the label text and / or
	 *                parameters. By default, the label text is a human-friendly version of `$name`.
	 *                However, you can specify the label manually as a string, or both the label
	 *                text and options as an array, i.e.:
	 *                `array('label text' => array('class' => 'foo', 'any' => 'other options'))`.
	 *              - `'type'` _string_: The type of form field to render. Available default options
	 *                are: `'text'`, `'textarea'`, `'select'`, `'checkbox'`, `'password'` or
	 *                `'hidden'`, as well as any arbitrary type (i.e. HTML5 form fields).
	 *              - `'template'` _string_: Defaults to `'template'`, but can be set to any named
	 *                template string, or an arbitrary HTML fragment. For example, to change the
	 *                default wrapper tag from `<div />` to `<li />`, you can pass the following:
	 *                `'<li{:wrap}>{:label}{:input}{:error}</li>'`.
	 *              - `'wrap'` _array_: An array of HTML attributes which will be embedded in the
	 *                wrapper tag.
	 *              - `list` _array_: If `'type'` is set to `'select'`, `'list'` is an array of
	 *                key/value pairs representing the `$list` parameter of the `select()` method.
	 * @return string Returns a form input (the input type is based on the `'type'` option), with
	 *         label and error message, wrapped in a `<div />` element.
	 */
	public function field($name, array $options = array()) {
		if (is_array($name)) {
			return $this->_fields($name, $options);
		}
		$defaults = array(
			'label' => null,
			'type' => isset($options['list']) ? 'select' : 'text',
			'template' => 'field',
			'wrap' => array(),
			'list' => null
		);
		$type = isset($options['type']) ? $options['type'] : $defaults['type'];

		if ($this->_context->strings('field-' . $type)) {
			$defaults['template'] = 'field-' . $type;
		}
		list($options, $fieldOptions) = $this->_options($defaults, $options);
		list(, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);

		if ($options['template'] != $defaults['template']) {
			$template = $options['template'];
		}
		$wrap = $options['wrap'];
		$type = $options['type'];
		$label = $input = null;

		if (($options['label'] === null || $options['label']) && $options['type'] != 'hidden') {
			if (!$options['label']) {
				$options['label'] = Inflector::humanize(preg_replace('/[\[\]\.]/', '_', $name));
			}
			$label = $this->label(isset($options['id']) ? $options['id'] : '', $options['label']);
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
		return $this->_render(__METHOD__, $template, compact('wrap', 'label', 'input', 'error'));
	}

	/**
	 * Helper method used by `Form::field()` for iterating over an array of multiple fields.
	 *
	 * @see lithium\template\helper\Form::field()
	 * @param array $fields An array of fields to render.
	 * @param array $options The array of options to apply to all fields in the `$fields` array. See
	 *              the `$options` parameter of the `field` method for more information.
	 * @return string Returns the fields rendered by `field()`, each separated by a newline.
	 */
	protected function _fields(array $fields, array $options = array()) {
		$result = array();

		foreach ($fields as $field => $label) {
			if (is_numeric($field)) {
				$field = $label;
				unset($label);
			}
			$result[] = $this->field($field, compact('label') + $options);
		}
		return join("\n", $result);
	}

	/**
	 * Generates an HTML `<input type="submit" />` object.
	 *
	 * @param string $title The title of the submit button.
	 * @param array $options Any options passed are converted to HTML attributes within the
	 *              `<input />` tag.
	 * @return string Returns a submit `<input />` tag with the given title and HTML attributes.
	 */
	public function submit($title = null, array $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, null, $options);
		return $this->_render(__METHOD__, $template, compact('title', 'options'));
	}

	/**
	 * Generates an HTML `<textarea>...</textarea>` object.
	 *
	 * @param string $name The name of the field.
	 * @param array $options The options to be used when generating the `<textarea />` tag pair,
	 *              which are as follows:
	 *              - `'value'` _string_: The content value of the field.
	 *              - Any other options specified are rendered as HTML attributes of the element.
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
	 * @param array $options All options passed are rendered as HTML attributes.
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
	 * @param array $options Options to be used when generating the checkbox `<input />` element:
	 *              - `'checked'` _boolean_: Whether or not the field should be checked by default.
	 *              - `'value'` _mixed_: if specified, it will be used as the 'value' html
	 *                attribute and no hidden input field will be added
	 *              - Any other options specified are rendered as HTML attributes of the element.
	 * @return string Returns a `<input />` tag with the given name and HTML attributes.
	 */
	public function checkbox($name, array $options = array()) {
		$defaults = array('value' => '1', 'hidden' => true);
		$options += $defaults;
		$default = $options['value'];
		$out = '';

		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		list($scope, $options) = $this->_options($defaults, $options);

		if (!isset($options['checked'])) {
			if ($this->_binding && $bound = $this->_binding->data($name)) {
				$options['checked'] = !($bound === $default);
			} else {
				$options['checked'] = ($scope['value'] != $default);
			}
		}
		if ($scope['hidden']) {
			$out = $this->hidden($name, array('value' => '', 'id' => false));
		}
		$options['value'] = $scope['value'];
		return $out . $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	/**
	 * Generates an HTML `<input type="password" />` object.
	 *
	 * @param string $name The name of the field.
	 * @param array $options An array of HTML attributes with which the field should be rendered.
	 * @return string Returns a `<input />` tag with the given name and HTML attributes.
	 */
	public function password($name, array $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		unset($options['value']);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	/**
	 * Generates an HTML `<input type="hidden" />` object.
	 *
	 * @param string $name The name of the field.
	 * @param array $options An array of HTML attributes with which the field should be rendered.
	 * @return string Returns a `<input />` tag with the given name and HTML attributes.
	 */
	public function hidden($name, array $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	/**
	 * Generates an HTML `<label></label>` object.
	 *
	 * @param string $name The DOM ID of the field that the label is for.
	 * @param string $title The content inside the `<label></label>` object.
	 * @param array $options Besides HTML attributes, this parameter allows one additional flag:
	 *              - `'escape'` _boolean_: Defaults to `true`. Indicates whether the title of the
	 *                label should be escaped. If `false`, it will be treated as raw HTML.
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
	 *              If `$key` is not set in the array of errors, or if `$key` is `true`, the first
	 *              available error is used.
	 * @param array $options Any rendering options or HTML attributes to be used when rendering
	 *              the error.
	 * @return string Returns a rendered error message based on the `'error'` string template.
	 */
	public function error($name, $key = null, array $options = array()) {
		$defaults = array('class' => 'error');
		list(, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		$options += $defaults;
		$result = '';

		if (isset($options['value'])) {
			unset($options['value']);
		}

		if (!$this->_binding || !$content = $this->_binding->errors($name)) {
			return null;
		}

		if (is_array($content)) {
			$errors = $content;

			if ($key !== null) {
				$content = !isset($errors[$key]) || $key === true ? reset($errors) : $errors[$key];
			} else {
				foreach ($errors as $content) {
					$result .= $this->_render(__METHOD__, $template, compact('content', 'options'));
				}
				return $result;
			}
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
		$options = $this->_generators($method, $name, $options);

		$hasValue = (
			(!isset($options['value']) || $options['value'] === null) &&
			$name && $this->_binding && $value = $this->_binding->data($name)
		);
		if ($hasValue) {
			$options['value'] = $value;
		}
		if (isset($options['default']) && empty($options['value'])) {
			$options['value'] = $options['default'];
		}
		unset($options['default']);

		if (strpos($name, '.')) {
			$name = explode('.', $name);
			$first = array_shift($name);
			$name = $first . '[' . join('][', $name) . ']';
		}
		$tplKey = isset($options['template']) ? $options['template'] : $method;
		$template = isset($this->_templateMap[$tplKey]) ? $this->_templateMap[$tplKey] : $tplKey;
		return array($name, $options, $template);
	}

	/**
	 * Iterates over the configured attribute generators, and modifies the settings for a tag.
	 *
	 * @param string $method The name of the helper method which was called, i.e. `'text'`,
	 *               `'select'`, etc.
	 * @param string $name The name of the field whose attributes are being generated. Some helper
	 *               methods, such as `create()` and `end()`, are not field-based, and therefore
	 *               will have no name.
	 * @param array $options The options and HTML attributes that will be used to generate the
	 *              helper output.
	 * @return array Returns the value of the `$options` array, modified by the attribute generators
	 *         added in the `'attributes'` key of the helper's configuration. Note that if a
	 *         generator is present for a field whose value is `false`, that field will be removed
	 *         from the array.
	 */
	protected function _generators($method, $name, $options) {
		foreach ($this->_config['attributes'] as $key => $generator) {
			if ($generator && !isset($options[$key])) {
				if (($attr = $generator($method, $name, $options)) !== null) {
					$options[$key] = $attr;
				}
				continue;
			}
			if ($generator && $options[$key] === false) {
				unset($options[$key]);
			}
		}
		return $options;
	}
}

?>