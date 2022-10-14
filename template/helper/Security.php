<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\template\helper;

use lithium\aop\Filters;

/**
 * The `Security` helper is responsible for various tasks associated with verifying the authenticity
 * of requests, including embedding secure tokens to protect against CSRF attacks, and signing forms
 * to prevent adding or removing fields, or tampering with fields that are designated 'locked'.
 *
 * @see lithium\security\validation\RequestToken
 */
class Security extends \lithium\template\Helper {

	protected $_classes = [
		'requestToken' => 'lithium\security\validation\RequestToken',
		'formSignature' => 'lithium\security\validation\FormSignature'
	];

	protected $_state = [];

	/**
	 * Constructor. Configures the helper with the default settings for interacting with
	 * security tokens.
	 *
	 * @param array $config
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['sessionKey' => 'security.token', 'salt' => null];
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
	public function requestToken(array $options = []) {
		$defaults = ['name' => 'security.token', 'id' => false];
		$options += $defaults;
		$requestToken = $this->_classes['requestToken'];

		$flags = array_intersect_key($this->_config, ['sessionKey' => '', 'salt' => '']);
		$value = $requestToken::key($flags);

		$name = $options['name'];
		unset($options['name']);
		return $this->_context->form->hidden($name, compact('value') + $options);
	}

	/**
	 * Binds the `Security` helper to the `Form` helper to create a signature used to secure form
	 * fields against tampering.
	 *
	 * First `FormSignature` must be provided with a secret unique to your app. This is best
	 * done in the bootstrap process. The secret key should be a random lengthy string.
	 * ```php
	 * use lithium\security\validation\FormSignature;
	 * FormSignature::config(['secret' => 'a long secret key']);
	 * ```
	 *
	 * In the view call the `sign()` method before creating the form.
	 * ```php
	 * <?php $this->security->sign(); ?>
	 * <?=$this->form->create(...); ?>
	 *     // Form fields...
	 * <?=$this->form->end(); ?>
	 * ```
	 *
	 * In the corresponding controller action verify the signature.
	 * ```php
	 * if ($this->request->is('post') && !FormSignature::check($this->request)) {
	 *     // The key didn't match, meaning the request has been tampered with.
	 * }
	 * ```
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
		$form = $form ?: $this->_context->form;

		if (isset($state[spl_object_hash($form)])) {
			return;
		}

		Filters::apply($form, 'create', function($params, $next) use ($form) {
			$this->_state[spl_object_hash($form)] = [
				'fields' => [],
				'locked' => [],
				'excluded' => []
			];
			return $next($params);
		});

		Filters::apply($form, 'end', function($params, $next) use ($form) {
			$id = spl_object_hash($form);

			if (!$this->_state[$id]) {
				return $next($params);
			}
			$formSignature = $this->_classes['formSignature'];

			$value = $formSignature::key($this->_state[$id]);
			echo $form->hidden('security.signature', compact('value'));

			$this->_state[$id] = [];
			return $next($params);
		});

		Filters::apply($form, '_defaults', function($params, $next) use ($form) {
			$defaults = [
				'locked' => ($params['method'] === 'hidden' && $params['name'] !== '_method'),
				'exclude' => $params['name'] === '_method'
			];
			$options = $params['options'];

			$options += $defaults;
			$params['options'] = array_diff_key($options, $defaults);
			$result = $next($params);

			if ($params['method'] === 'label') {
				return $result;
			}
			$value = isset($params['options']['value']) ? $params['options']['value'] : "";

			$type = [
				$options['exclude']  => 'excluded',
				!$options['exclude'] => 'fields',
				$options['locked']   => 'locked'
			];
			if (!$name = preg_replace('/(\.\d+)+$/', '', $params['name'] ?? '')) {
				return $result;
			}
			$this->_state[spl_object_hash($form)][$type[true]][$name] = $value;
			return $result;
		});
	}
}

?>