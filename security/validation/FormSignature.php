<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\security\validation;

use Exception;
use lithium\core\ConfigException;
use lithium\util\Set;

/**
 * The `FormSignature` class cryptographically signs web forms, to prevent adding or removing
 * fields, or modifying hidden (locked) fields.
 *
 * Using the `Security` helper, `FormSignature` calculates a hash of all fields in a form, so that
 * when the form is submitted, the fields may be validated to ensure that none were added or
 * removed, and that fields designated as _locked_ have not had their values altered.
 *
 * To enable form signing in a view, configure the class with an app specific secret, then
 * simply call `$this->security->sign()` before generating your form. In the controller, you
 * may then validate the request by passing `$this->request` to the `check()` method.
 *
 * @see lithium\template\helper\Security::sign()
 */
class FormSignature {

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected static $_classes = [
		'hash' => 'lithium\security\Hash'
	];

	/**
	 * Must be set manually to a unique string i.e.
	 * `wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY`
	 *
	 * @var string
	 */
	protected static $_secret = null;

	/**
	 * Configures the class or retrieves current class configuration.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'classes'` _array_: May be used to inject dependencies.
	 *        - `'secret'` _string_: *Must* be provided.
	 * @return array|void If `$config` is empty, returns an array with the current configurations.
	 */
	public static function config(array $config = []) {
		if (!$config) {
			return [
				'classes' => static::$_classes,
				'secret' => static::$_secret
			];
		}
		if (isset($config['classes'])) {
			static::$_classes = $config['classes'] + static::$_classes;
		}
		if (isset($config['secret'])) {
			static::$_secret = $config['secret'];
		}
	}

	/**
	 * Generates form signature string from form data.
	 *
	 * @param array $data An array of fields, locked fields and excluded fields.
	 * @return string The form signature string.
	 */
	public static function key(array $data) {
		$data += [
			'fields' => [],
			'locked' => [],
			'excluded' => []
		];
		return static::_compile(
			array_keys(Set::flatten($data['fields'])),
			$data['locked'],
			array_keys($data['excluded'])
		);
	}

	/**
	 * Validates form data using an embedded form signature string. The form signature string
	 * must be embedded in `security.signature` alongside the other data to check against.
	 *
	 * Note: Will ignore any other data inside `security.*`.
	 *
	 * @param array|object $data The form data as an array or an
	 *        object with the data inside the `data` property.
	 * @return boolean `true` if the form data is valid, `false` if not.
	 */
	public static function check($data) {
		if (is_object($data) && isset($data->data)) {
			$data = $data->data;
		}
		if (!isset($data['security']['signature'])) {
			throw new Exception('Unable to check form signature. Cannot find signature in data.');
		}
		$signature = $data['security']['signature'];
		unset($data['security']);

		$parsed = static::_parse($signature);
		$data = Set::flatten($data);

		if (array_intersect_assoc($data, $parsed['locked']) != $parsed['locked']) {
			return false;
		}
		$fields = array_diff(
			array_keys($data),
			array_keys($parsed['locked']),
			$parsed['excluded']
		);
		return $signature === static::_compile($fields, $parsed['locked'], $parsed['excluded']);
	}

	/**
	 * Compiles form signature string. Will normalize input data and `urlencode()` it.
	 *
	 * The signature is calculated over locked and exclude fields as well as a hash
	 * of $fields. The $fields data will not become part of the final form signature
	 * string. The $fields hash is not signed itself as the hash will become part
	 * of the form signature string which is already signed.
	 *
	 * @param array $fields
	 * @param array $locked
	 * @param array $excluded
	 * @return string The compiled form signature string that should be submitted
	 *         with the form data in the form of:
	 *         `<serialized locked>::<serialized excluded>::<signature>`.
	 */
	protected static function _compile(array $fields, array $locked, array $excluded) {
		$hash = static::$_classes['hash'];

		sort($fields, SORT_STRING);
		ksort($locked, SORT_STRING);
		sort($excluded, SORT_STRING);

		foreach (['fields', 'excluded', 'locked'] as $list) {
			${$list} = urlencode(serialize(${$list}));
		}
		$hash = $hash::calculate($fields);
		$signature = static::_signature("{$locked}::{$excluded}::{$hash}");

		return "{$locked}::{$excluded}::{$signature}";
	}

	/**
	 * Calculates signature over given data.
	 *
	 * Will first derive a signing key from the secret key and current date, then
	 * calculate the HMAC over given data. This process is modelled after Amazon's
	 * _Message Signature Version 4_ but uses less key derivations as we don't have
	 * more information at our hands.
	 *
	 * During key derivation the strings `li3,1` and `li3,1_form` are inserted. `1`
	 * denotes the version of our signature algorithm and should be raised when the
	 * algorithm is changed. Derivation is needed to not reveal the secret key.
	 *
	 * Note: As the current date (year, month, day) is used to increase key security by
	 * limiting its lifetime, a possible sideeffect is that a signature doen't verify if it is
	 * generated on day N and verified on day N+1.
	 *
	 * @link http://docs.aws.amazon.com/general/latest/gr/sigv4-calculate-signature.html
	 * @param string $data The data to calculate the signature for.
	 * @return string The signature.
	 */
	protected static function _signature($data) {
		$hash = static::$_classes['hash'];

		if (empty(static::$_secret)) {
			$message  = 'Form signature requires a secret key. ';
			$message .= 'Please see documentation on how to configure a key.';
			throw new ConfigException($message);
		}
		$key = 'li3,1' . static::$_secret;
		$key = $hash::calculate(date('YMD'), ['key' => $key, 'raw' => true]);
		$key = $hash::calculate('li3,1_form', ['key' => $key, 'raw' => true]);

		return $hash::calculate($data, ['key' => $key]);
	}

	/**
	 * Parses form signature string.
	 *
	 * Note: The parsed signature is not returned as it's not needed. The signature
	 *       is verified by re-compiling the form signature string with the retrieved
	 *       signature.
	 *
	 * @param string $string
	 * @return array
	 */
	protected static function _parse($string) {
		if (substr_count($string, '::') !== 2) {
			throw new Exception('Possible data tampering: form signature string has wrong format.');
		}
		list($locked, $excluded) = explode('::', $string, 3);

		return [
			'locked' => unserialize(urldecode($locked)),
			'excluded' => unserialize(urldecode($excluded))
		];
	}
}

?>