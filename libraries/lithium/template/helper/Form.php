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

/**
 * A helper class to facilitate generating, processing and securing HTML forms. By default, `Form`
 * will simply generate HTML forms and widgets, but by creating a form with a _binding object_,
 * the helper can pre-fill form input values, render error messages, and introspect column types.
 *
 * For example:
 * {{{// In controller code:
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
		'file'                 => '<input type="file" name="{:name}"{:options} />',
		'form'                 => '<form action="{:url}"{:options}>',
		'form-end'             => '</form>',
		'hidden'               => '<input type="hidden" name="{:name}"{:options} />',
		'input'                => '<div{:wrap}>{:label}{:input}{:error}</div>',
		'input-checkbox'       => '<div{:wrap}>{:input}{:label}{:error}</div>',
		'label'                => '<label for="{:name}"{:options}>{:title}</label>',
		'legend'               => '<legend>{:content}</legend>',
		'option-group'         => '<optgroup label="{:label}"{:options}>',
		'option-group-end'     => '</optgroup>',
		'password'             => '<input type="password" name="{:name}"{:options} />',
		'radio'             => '<input type="radio" name="{:name}" id="{:id}"{:options} />{:label}',
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

	public function __construct($config = array()) {
		$defaults = array(
			'base' => array(), 'text' => array(), 'textarea' => array(),
			'select' => array('multiple' => false)
		);
		parent::__construct((array) $config + $defaults);
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
	 * @param array $config An associative array where the keys are `Form` method names, and the
	 *              values are arrays of configuration options to be included in the `$options`
	 *              parameter of each method specified.
	 * @return array Returns an array containing the currently set per-method configurations, and
	 *         an array of the currently set template overrides (in the `'templates'` array key).
	 * @see lithium\template\helper\Form::$_templateMap
	 */
	public function config($config = array()) {
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
	 * @param object $binding
	 * @param array $options
	 * @return string Returns a `<form />` open tag with the `action` attribute defined by either
	 *         the `'action'` or `'url'` options (defaulting to the current page if none is
	 *         specified), the HTTP method is defined by the `'type'` option, and any HTML
	 *         attributes passed in `$options`.
	 */
	public function create($binding = null, $options = array()) {
		$defaults = array(
			'url' => null,
			'type' => null,
			'action' => $this->_context->request()->params['action'],
			'method' => $binding ? ($binding->exists() ? 'put' : 'post') : 'post'
		);
		list(, $options, $template) = $this->_defaults(__FUNCTION__, null, $options);
		$options = (array) $options + $defaults;
		$_binding =& $this->_binding;
		$method = __METHOD__;

		$filter = function($self, $params, $chain) use ($template, $method, $defaults, &$_binding) {
			extract($params);
			$_binding = $binding;
			$append = '';

			if ($options['type'] == 'file') {
				if (strtolower($options['method']) == 'get') {
					$options['method'] = 'post';
				}
				$options['enctype'] = 'multipart/form-data';
			}
			unset($options['type']);

			if (!in_array(strtolower($options['method']), array('get', 'post'))) {
				$append .= $self->hidden('_method', array(
					'name' => '_method', 'value' => strtoupper($options['method'])
				));
			}

			$url = $options['url'] ?: array('action' => $options['action']);
			unset($options['url'], $options['action']);
			$options['method'] = strtoupper($options['method']);

			return $self->invokeMethod('_render', array(
				$method, $template, compact('url', 'options')
			));
		};
		return $this->_filter(__METHOD__, compact('binding', 'options'), $filter);
	}

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
	 * Generates an HTML `<input type="submit" />` object.
	 *
	 * @param string $title The title of the submit button.
	 * @param array $options
	 * @return string Returns a submit `<input />` tag with the given title and HTML attributes.
	 */
	public function submit($title = null, $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, null, $options);
		return $this->_render(__METHOD__, $template, compact('title', 'options'));
	}

	public function textarea($name, $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		$value = isset($options['value']) ? $options['value'] : '';
		unset($options['value']);
		return $this->_render(__METHOD__, $template, compact('name', 'options', 'value'));
	}

	public function text($name, $options = array()) {
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
	public function select($name, $list = array(), $options = array()) {
		$defaults = array('empty' => false, 'value' => null);
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);

		$options += $defaults;
		$val = $options['value'];
		$empty = $options['empty'];
		unset($options['value'], $options['empty']);

		if ($empty) {
			$list = array('' => ($empty === true) ? '' : $empty) + $list;
		}
		$startTemplate = ($options['multiple']) ? 'select-multi-start' : 'select-start';
		$output = $this->_render(__METHOD__, $startTemplate, compact('name', 'options'));

		foreach ($list as $value => $title) {
			$selected = false;

			if (is_array($val) && in_array($value, $val)) {
				$selected = true;
			} elseif ($val == $value) {
				$selected = true;
			}
			$options = $selected ? array('selected' => true) : array();

			$output .= $this->_render(__METHOD__, 'select-option', compact(
				'value', 'title', 'options'
			));
		}
		return $output . $this->_context->strings('select-end');
	}

	public function checkbox($name, $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);

		if (!isset($options['checked'])) {
			$options['checked'] = isset($options['value']) ? $options['value'] : false;
		}
		unset($options['value']);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	public function password($name, $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	public function hidden($name, $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	public function label($name, $title = null, $options = array()) {
		$title = $title ?: Inflector::humanize($name);
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'title', 'options'));
	}

	protected function _defaults($method, $name, $options) {
		$methodConfig = isset($this->_config[$method]) ? $this->_config[$method] : array();
		$options += $methodConfig + $this->_config['base'];

		if ($name && $this->_binding && (!isset($options['value']) || empty($options['value']))) {
			$options['value'] = $this->_binding->data($name);
		}

		if (isset($options['default'])) {
			$options['value'] = !empty($options['value']) ? $options['value'] : $options['default'];
			unset($options['default']);
		}
		$template = isset($this->_templateMap[$method]) ? $this->_templateMap[$method] : $method;
		return array($name, $options, $template);
	}
}

?>