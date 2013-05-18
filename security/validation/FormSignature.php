<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\security\validation;

use lithium\util\String;
use lithium\util\Set;

/**
 * The `FormSignature` class cryptographically signs web forms, to prevent adding or removing
 * fields, or modifying hidden (locked) fields.
 *
 * Using the `Security` helper, `FormSignature` calculates a hash of all fields in a form, so that
 * when the form is submitted, the fields may be validated to ensure that none were added or
 * removed, and that fields designated as _locked_ have not had their values altered.
 *
 * To enable form signing in a view, simply call `$this->security->sign()` before generating your
 * form. In the controller, you may then validate the request by passing `$this->request` to the
 * `check()` method.
 *
 * @see lithium\template\helper\Security::sign()
 */
class FormSignature {

	/**
	 * Class dependencies.
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'password' => 'lithium\security\Password'
	);

	protected static $_salt = '$2a$10$NuNTOeXv4OHpPJtbdAmfRe';

	/**
	 * Used to get or reconfigure dependencies with custom classes.
	 *
	 * @param array $config When assigning new configuration, should be an array containing a
	 *              `'classes'` key.
	 * @return array If `$config` is empty, returns an array with a `'classes'` key containing class
	 *         dependencies. Otherwise returns `null`.
	 */
	public static function config(array $config = array()) {
		if (!$config) {
			return array('classes' => static::$_classes, 'salt' => static::$_salt);
		}

		foreach ($config as $key => $val) {
			$key = "_{$key}";

			if (!isset(static::${$key})) {
				continue;
			}
			if (is_array(static::${$key})) {
				static::${$key} = $val + static::${$key};
			} else {
				static::${$key} = $val;
			}
		}
	}

	public static function key(array $data) {
		$classes = static::$_classes;
		$data += array('fields' => array(), 'locked' => array(), 'excluded' => array());

		$fields = array_keys(Set::flatten($data['fields']));
		$excluded = array_keys($data['excluded']);
		$locked = $data['locked'];

		sort($fields, SORT_STRING);
		sort($excluded, SORT_STRING);
		ksort($locked, SORT_STRING);

		foreach (array('fields', 'excluded', 'locked') as $list) {
			${$list} = urlencode(serialize(${$list}));
		}
		$hash = $classes['password']::hash($fields, static::$_salt);
		$hash = $classes['password']::hash("{$locked}::{$excluded}::{$hash}", static::$_salt);

		return "{$locked}::{$excluded}::{$hash}";
	}

	public static function check($data) {
		if (is_object($data) && isset($data->data)) {
			$data = $data->data;
		}
		if (!isset($data['security']['signature'])) {
			return false;
		}
		$signature = $data['security']['signature'];
		unset($data['security']);
		$data = Set::flatten($data);
		$fields = array_keys($data);

		list($locked, $excluded, $hash) = explode('::', $signature, 3);
		$locked = unserialize(urldecode($locked));
		$excluded = unserialize(urldecode($excluded));
		$fields = array_diff($fields, $excluded);

		if (array_intersect_assoc($data, $locked) != $locked) {
			return false;
		}
		return $signature === static::key(compact('fields', 'locked', 'excluded'));
	}
}

?>