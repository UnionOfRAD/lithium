<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template\helper;

/**
 * The `Security` helper is responsible for various tasks associated with verifying the authenticity
 * of requests, including embedding secure tokens to protect against CSRF attacks, and signing forms
 * to prevent adding or removing fields, or tampering with fields that are designated 'locked'.
 *
 * @see lithium\security\validation\RequestToken
 */
class Security extends \lithium\template\Helper {

	protected $_classes = array(
		'requestToken' => 'lithium\security\validation\RequestToken',
		'formSignature' => 'lithium\security\validation\FormSignature'
	);

	protected $_state = array();

	/**
	 * Configures the helper with the default settings for interacting with security tokens.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$defaults = array('sessionKey' => 'security.token', 'salt' => null);
		parent::__construct($config + $defaults);
	}

	/**
	 * Generates a request key used to protect your forms against CSRF attacks. See the
	 * `RequestToken` class for examples and proper usage.
	 *
	 * @see lithium\security\validation\RequestToken
	 * @param array $options Options used as HTML when generating the field.
	 * @return string Returns a hidden `<input />` field containing a request-specific CSRF token
	 *         key.
	 */
	public function requestToken(array $options = array()) {
		$defaults = array('name' => 'security.token', 'id' => false);
		$options += $defaults;
		$requestToken = $this->_classes['requestToken'];

		$flags = array_intersect_key($this->_config, array('sessionKey' => '', 'salt' => ''));
		$value = $requestToken::key($flags);

		$name = $options['name'];
		unset($options['name']);
		return $this->_context->form->hidden($name, compact('value') + $options);
	}

	/**
	 * Binds the `Security` helper to the `Form` helper to create a signature used to secure form
	 * fields against tampering.
	 *
	 * {{{
	 * // view:
	 * <?php $this->security->sign(); ?>
	 * <?=$this->form->create(...); ?>
	 * 	// Form fields...
	 * <?=$this->form->end(); ?>
	 * }}}
	 *
	 * {{{
	 * // controller:
	 * if ($this->request->is('post') && !FormSignature::check($this->request)) {
	 * 	// The key didn't match, meaning the request has been tampered with.
	 * }
	 * }}}
	 *
	 * Calling this method before a form is created adds two additional options to the `$options`
	 * parameter in all form inputs:
	 *
	 * - `'locked'` _boolean_: If `true`, _locks_ the value specified in the field when the field
	 *   is generated, such that tampering with the value will invalidate the signature. Defaults
	 *   to `true` for hidden fields, and `false` for all other form inputs.
	 *
	 * - `'exclude'` _boolean_: If `true`, this field and all subfields of the same name will be
	 *   excluded from the signature calculation. This is useful in situations where fields may be
	 *   added dynamically on the client side. Defaults to `false`.
	 *
	 * @see lithium\template\helper\Form
	 * @see lithium\security\validation\FormSignature
	 * @param object $form Optional. Allows specifying an instance of the `Form` helper manually.
	 * @return void
	 */
	public function sign($form = null) {
		$state =& $this->_state;
		$classes = $this->_classes;
		$form = $form ?: $this->_context->form;
		$id = spl_object_hash($form);
		$hasBound = isset($state[$id]);

		if ($hasBound) {
			return;
		}

		$form->applyFilter('create', function($self, $params, $chain) use ($form, &$state) {
			$id = spl_object_hash($form);
			$state[$id] = array('fields' => array(), 'locked' => array(), 'excluded' => array());
			return $chain->next($self, $params, $chain);
		});

		$form->applyFilter('end', function($self, $params, $chain) use ($form, &$state, $classes) {
			$id = spl_object_hash($form);

			if (!$state[$id]) {
				return $chain->next($self, $params, $chain);
			}
			$value = $classes['formSignature']::key($state[$id]);
			echo $form->hidden('security.signature', compact('value'));

			$state[$id] = array();
			return $chain->next($self, $params, $chain);
		});

		$form->applyFilter('_defaults', function($self, $params, $chain) use ($form, &$state) {
			$defaults = array('locked' => false, 'exclude' => false);
			$options = $params['options'];

			if ($params['method'] === 'hidden' && !isset($options['locked'])) {
				$options['locked'] = true;
			}
			$options += $defaults;
			$params['options'] = array_diff_key($options, $defaults);
			$result = $chain->next($self, $params, $chain);

			if (isset($options['exclude']) && $options['exclude']) {
				return $result;
			}
			$value = isset($params['options']['value']) ? $params['options']['value'] : "";

			$type = array(
				$options['exclude']  => 'excluded',
				!$options['exclude'] => 'fields',
				$options['locked']   => 'locked'
			);
			if (!$name = preg_replace('/(\.\d+)+$/', '', $params['name'])) {
				return $result;
			}
			$state[spl_object_hash($form)][$type[true]][$name] = $value;
			return $result;
		});
	}
}

?>