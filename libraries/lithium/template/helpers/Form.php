<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template\helpers;

use \lithium\util\Set;

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
		'form'                 => '<form {:options}>',
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
		'select-option'        => '<option value="{:value}"{:options}>{:content}</option>',
		'select-end'           => '</select>',
		'submit'               => '<input type="submit"{:options} />',
		'submit-image'         => '<input type="image" src="{:url}"{:options} />',
		'text'                 => '<input type="text" name="{:name}"{:options} />',
		'textarea'             => '<textarea name="{:name}"{:options}>{:content}</textarea>',
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
	 * @see lithium\template\helpers\Form::config()
	 */
	protected $_templateMap = array();

	public function __construct($config = array()) {
		$defaults = array('base' => array(), 'text' => array(), 'textarea' => array());
		parent::__construct($config + $defaults);
	}

	/**
	 * Allows you to configure a default set of options which are included on a per-method basis,
	 * and configure method template overrides.
	 *
	 * To force all `<label />` elements to have a default `class` attribute value of "foo"`, simply
	 * do the following:
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
	 * @see lithium\template\helpers\Form::$_templateMap
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

	public function text($name, $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	public function password($name, $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'options'));
	}

	public function label($name, $title, $options = array()) {
		list($name, $options, $template) = $this->_defaults(__FUNCTION__, $name, $options);
		return $this->_render(__METHOD__, $template, compact('name', 'title', 'options'));
	}

	protected function _defaults($method, $name, $options) {
		$methodConfig = isset($this->_config[$method]) ? $this->_config[$method] : array();
		$options += $methodConfig + $this->_config['base'];

		if (isset($options['default'])) {
			$options['value'] = isset($options['value']) ? $options['value'] : $options['default'];
			unset($options['default']);
		}
		$template = isset($this->_templateMap[$method]) ? $this->_templateMap[$method] : $method;
		return array($name, $options, $template);
	}
}

?>