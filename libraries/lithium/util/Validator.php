<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util;

use \lithium\util\Set;
use \InvalidArgumentException;

class Validator extends \lithium\core\StaticObject {

	/**
	 * An array of validation rules.  May contain a single regular expression, an array of regular
	 * expressions (where the array keys define various possible 'formats' of the same rule), or a
	 * closure which accepts a value to be validated, and an array of options, and returns a
	 * boolean value, indicating whether the validation succeeded or failed.
	 *
	 * @var array
	 * @see lithium\util\Validator::add()
	 * @see lithium\util\Validator::rule()
	 */
	protected static $_rules = array();

	/**
	 * Two-dimensional array of closures which are invoked on a value before a validation is
	 * performed.  The array keys of the first level are the validation to be performed, i.e.
	 * `'alphaNumeric'` for `Validator::isAlphaNumeric()`, and the second level is simply a
	 * numeric array, indicating the order in which the closures should be executed.
	 *
	 * @var array
	 * @see lithium\util\Validator::filter()
	 * @see lithium\util\Validator::rule()
	 * @see lithium\util\Validator::$_postFilters
	 */
	protected static $_preFilters = array();

	/**
	 * Two-dimensional array of closures which are invoked on a value after a validation succeeds.
	 * See corresponding `$_preFilters` array for more information.  Unlike pre-filters, these
	 * post-filters provide an extra layer of validation if the primary rule succeeds. Often these
	 * filters are used for more in-depth checking, i.e. validating that the host name of an email
	 * address resolves to a valid IP, should a simple regex check succeed.
	 *
	 * @var array
	 * @see lithium\util\Validator::filter()
	 * @see lithium\util\Validator::rule()
	 * @see lithium\util\Validator::$_preFilters
	 */
	protected static $_postFilters = array();

	protected static $_options = array(
		'ip' => array('contains' => false),
		'defaults' => array('contains' => true)
	);

	/**
	 * Initializes the list of default validation rules.
	 *
	 * @return void
	 */
	public static function __init() {
		$alnum = '[A-Fa-f0-9]';

		static::$_rules = array(
			'alphaNumeric' => '/^[\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]+$/mu',
			'blank'        => '/[^\\s]/',
			'creditCard'   => array(
				'amex'     => '/^3[4|7]\\d{13}$/',
				'bankcard' => '/^56(10\\d\\d|022[1-5])\\d{10}$/',
				'diners'   => '/^(?:3(0[0-5]|[68]\\d)\\d{11})|(?:5[1-5]\\d{14})$/',
				'disc'     => '/^(?:6011|650\\d)\\d{12}$/',
				'electron' => '/^(?:417500|4917\\d{2}|4913\\d{2})\\d{10}$/',
				'enroute'  => '/^2(?:014|149)\\d{11}$/',
				'jcb'      => '/^(3\\d{4}|2100|1800)\\d{11}$/',
				'maestro'  => '/^(?:5020|6\\d{3})\\d{12}$/',
				'mc'       => '/^5[1-5]\\d{14}$/',
				'solo'     => '/^(6334[5-9][0-9]|6767[0-9]{2})\\d{10}(\\d{2,3})?$/',
				'switch'   => '/^(?:49(03(0[2-9]|3[5-9])|11(0[1-2]|7[4-9]|8[1-2])|36[0-9]{2})' .
				              '\\d{10}(\\d{2,3})?)|(?:564182\\d{10}(\\d{2,3})?)|(6(3(33[0-4]' .
				              '[0-9])|759[0-9]{2})\\d{10}(\\d{2,3})?)$/',
				'visa'     => '/^4\\d{12}(\\d{3})?$/',
				'voyager'  => '/^8699[0-9]{11}$/',
				'fast'     => '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3' .
				              '(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$/'
			),
			'date'         => array(
				'dmy'      => '%^(?:(?:31(\\/|-|\\.|\\x20)(?:0?[13578]|1[02]))\\1|(?:(?:29|30)' .
				              '(\\/|-|\\.|\\x20)(?:0?[1,3-9]|1[0-2])\\2))(?:(?:1[6-9]|[2-9]\\d)?' .
				              '\\d{2})$|^(?:29(\\/|-|\\.|\\x20)0?2\\3(?:(?:(?:1[6-9]|[2-9]\\d)?' .
				              '(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])' .
				              '00))))$|^(?:0?[1-9]|1\\d|2[0-8])(\\/|-|\\.|\\x20)(?:(?:0?[1-9])|' .
				              '(?:1[0-2]))\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%',
				'mdy'      => '%^(?:(?:(?:0?[13578]|1[02])(\\/|-|\\.|\\x20)31)\\1|(?:(?:0?[13-9]|' .
				              '1[0-2])(\\/|-|\\.|\\x20)(?:29|30)\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d' .
				              '{2})$|^(?:0?2(\\/|-|\\.|\\x20)29\\3(?:(?:(?:1[6-9]|[2-9]\\d)?' .
				              '(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])' .
				              '00))))$|^(?:(?:0?[1-9])|(?:1[0-2]))(\\/|-|\\.|\\x20)(?:0?[1-9]|1' .
				              '\\d|2[0-8])\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%',
				'ymd'      => '%^(?:(?:(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579]' .
				              '[26])|(?:(?:16|[2468][048]|[3579][26])00)))(\\/|-|\\.|\\x20)' .
				              '(?:0?2\\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\\d)?\\d{2})(\\/|-|\\.|' .
				              '\\x20)(?:(?:(?:0?[13578]|1[02])\\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])' .
				              '\\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\\2(?:0?[1-9]|1\\d|2[0-8]' .
				              '))))$%',
				'dMy'      => '/^((31(?!\\ (Feb(ruary)?|Apr(il)?|June?|(Sep(?=\\b|t)t?|Nov)' .
				              '(ember)?)))|((30|29)(?!\\ Feb(ruary)?))|(29(?=\\ Feb(ruary)?\\ ' .
				              '(((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468]' .
				              '[048]|[3579][26])00)))))|(0?[1-9])|1\\d|2[0-8])\\ (Jan(uary)?|' .
				              'Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|' .
				              'Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)\\ ((1[6-9]|[2-9]' .
				              '\\d)\\d{2})$/',
				'Mdy'      => '/^(?:(((Jan(uary)?|Ma(r(ch)?|y)|Jul(y)?|Aug(ust)?|Oct(ober)?' .
				              '|Dec(ember)?)\\ 31)|((Jan(uary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)' .
				              '|(ne?))|Aug(ust)?|Oct(ober)?|(Sept|Nov|Dec)(ember)?)\\ (0?[1-9]' .
				              '|([12]\\d)|30))|(Feb(ruary)?\\ (0?[1-9]|1\\d|2[0-8]|(29(?=,?\\ ' .
				              '((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468]' .
				              '[048]|[3579][26])00)))))))\\,?\\ ((1[6-9]|[2-9]\\d)\\d{2}))$/',
				'My'       => '%^(Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|' .
				              'Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)[ /]((1[6-9]' .
				              '|[2-9]\\d)\\d{2})$%',
				'my'       => '%^(((0[123456789]|10|11|12)([- /.])(([1][9][0-9][0-9])|([2][0-9]' .
				              '[0-9][0-9]))))$%'
			),
			'hostname'     => '(?:[a-z0-9][-a-z0-9]*\.)*(?:[a-z0-9][-a-z0-9]{0,62})\.' .
			                  '(?:(?:[a-z]{2}\.)?[a-z]{2,4}|museum|travel)',
			'ip'           => '(?:(?:25[0-5]|2[0-4][0-9]|(?:(?:1[0-9])?|[1-9]?)[0-9])\.){3}' .
			                  '(?:25[0-5]|2[0-4][0-9]|(?:(?:1[0-9])?|[1-9]?)[0-9])',
			'money'        => array(
				'right'    => '/^(?!0,?\d)(?:\d{1,3}(?:([, .])\d{3})?(?:\1\d{3})*|(?:\d+))' .
				              '((?!\1)[,.]\d{2})?(?<!\x{00a2})\p{Sc}?$/u',
				'left'     => '/^(?!\x{00a2})\p{Sc}?(?!0,?\d)(?:\d{1,3}(?:([, .])\d{3})?' .
				              '(?:\1\d{3})*|(?:\d+))((?!\1)[,.]\d{2})?$/u'
			),
			'notEmpty'     => '/[^\s]+/m',
			'phone'        => array(
				'us'       => '/^(?:\+?1)?[-. ]?\\(?[2-9][0-8][0-9]\\)?[-. ]?[2-9][0-9]{2}[-. ]' .
			                  '?[0-9]{4}$/',
			),
			'postalCode'   => array(
				'uk'       => '/\\A\\b[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][ABD-HJLNP-UW-Z]{2}\\b\\z/i',
				'ca'       => '/\\A\\b[ABCEGHJKLMNPRSTVXY][0-9][A-Z] [0-9][A-Z][0-9]\\b\\z/i',
				'it'       => '/^[0-9]{5}$/i',
				'de'       => '/^[0-9]{5}$/i',
				'be'       => '/^[1-9]{1}[0-9]{3}$/i',
				'us'       => '/\\A\\b[0-9]{5}(?:-[0-9]{4})?\\b\\z/i'
			),
			'regex'        => '/^\/(.+)\/[gimsxu]*$/',
			'ssn'          => array(
				'dk'       => '/\\A\\b[0-9]{6}-[0-9]{4}\\b\\z/i',
				'nl'       => '/\\A\\b[0-9]{9}\\b\\z/i',
				'us'       => '/\\A\\b[0-9]{3}-[0-9]{2}-[0-9]{4}\\b\\z/i'
			),
			'time'         => '%^((0?[1-9]|1[012])(:[0-5]\d){0,2}([AP]M|[ap]m))$|^([01]\d|2[0-3])' .
			                  '(:[0-5]\d){0,2}$%',
			'boolean' => function($value) {
				return in_array($value, array(0, 1, '0', '1', true, false), true);
			},
			'decimal' => function($value, $format = null, $options = array()) {
				$defaults = array('precision' => null);
				$options += $defaults;

				$precision = '+(?:[eE][-+]?[0-9]+)?';
				$precision = $options['precision'] ? '{' . $options['precision'] . '}' : $precision;
				return (bool)preg_match("/^[-+]?[0-9]*\\.{1}[0-9]{$precision}$/", (string)$value);
			},
			'inList' => function($value, $format, $options) {
				$options += array('list' => array());
				return in_array($value, $options['list']);
			},
			'lengthBetween' => function($value, $format, $options) {
				$length = strlen($value);
				$options += array('min' => 1, 'max' => 255);
				return ($length >= $options['min'] && $length <= $options['max']);
			},
			'luhn' => function($value) {
				if (empty($value) || !is_string($value)) {
					return false;
				}
				$sum = 0;
				$length = strlen($value);

				for ($position = 1 - ($length % 2); $position < $length; $position += 2) {
					$sum += $value[$position];
				}
				for ($position = ($length % 2); $position < $length; $position += 2) {
					$number = $value[$position] * 2;
					$sum += ($number < 10) ? $number : $number - 9;
				}
				return ($sum % 10 == 0);
			},
			'numeric' => function($value) {
				return is_numeric($value);
			},
			'uuid' => "/{$alnum}{8}-{$alnum}{4}-{$alnum}{4}-{$alnum}{4}-{$alnum}{12}/"
		);

		static::$_rules['email'] = '/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`';
		static::$_rules['email'] .= '{|}~-]+)*@' . static::$_rules['hostname'] . '$/i';

		$urlChars = '([' . preg_quote('!"$&\'()*+,-.@_:;=') . '\/0-9a-z]|(%[0-9a-f]{2}))';
		$url = '/^(?:(?:https?|ftps?|file|news|gopher):\/\/)__strict__';
		$url .=  '(?:' . static::$_rules['ip'] . '|' . static::$_rules['hostname'] . ')';
		$url .= '(?::[1-9][0-9]{0,3})?(?:\/?|\/' . $urlChars . '*)?(?:\?' . $urlChars . '*)?';
		$url .= '(?:#' . $urlChars . '*)?$/i';

		static::$_rules['url'] = array(
			'strict' => str_replace('__strict__', '', $url),
			'loose' =>  str_replace('__strict__', '?', $url)
		);

		$emptyCheck = function($value) {
			if (empty($value) && $value != '0') {
				return false;
			}
		};
		static::$_preFilters['alphaNumeric'] = array($emptyCheck);
		static::$_preFilters['notEmpty'] = array($emptyCheck);

		static::$_preFilters['creditCard'] = array(function($value, $format, $options) {
			$value = str_replace(array('-', ' '), '', $value);
			return (strlen($value) < 13) ? false : $value;
		});

		static::$_postFilters['creditCard'] = array(function($value, $format, $options) {
			$options += array('deep' => false);
			return $options['deep'] ? Validator::isLuhn($value) : true;
		});
		$host = static::$_rules['hostname'];

		static::$_postFilters['email'] = array(function($value, $format, $options) use ($host) {
			$options += array('deep' => false);

			if (!$options['deep']) {
				return true;
			}

			if (preg_match('/@(' . $host . ')$/i', $value, $regs)) {
				if (getmxrr($regs[1], $mxhosts)) {
					return is_array($mxhosts);
				}
				return false;
			}
		});
	}

	/**
	 * Maps method calls to validation rule names.  For example, a validation rule that would
	 * normally be called as `Validator::rule('email', 'foo@bar.com')` can also be called as
	 * `Validator::isEmail('foo@bar.com')`.
	 *
	 * @param string $method The name of the method called, i.e. `'isEmail'` or `'isCreditCard'`.
	 * @param array $args
	 * @return boolean
	 */
	public static function __callStatic($method, $args = array()) {
		if (!isset($args[0])) {
			return false;
		}
		$args += array(1 => 'any', 2 => array());
		$rule = preg_replace("/^is([A-Z][A-Za-z0-9]+)$/", '$1', $method);
		$rule[0] = strtolower($rule[0]);
		return static::rule($rule, $args[0], $args[1], $args[2]);
	}

	/**
	 * Checks a set of values against a specified rules list.
	 *
	 * This method may be used to validate any arbitrary array data against a set of validation
	 * rules.
	 *
	 * @param object $values An array of key/value pairs, where the values are to be checked.
	 * @param string $rules array of rules to check against object properties
	 * @return mixed When all validation rules pass
	 * @todo Bring over validation loop from Model, determine formats/options, implement.
	 */
	public static function check($values, $rules, $options = array()) {
		$rule = 'isNotEmpty';
		$__check = function($field, $rules) use ($values, $rule, &$__check) {
			$data = isset($values[$field]) ? array($values[$field]) : array();
			$errors = array();
			if (is_array($rules)) {
				if (!empty($rules[0])) {
					$multiple = array();
					foreach ($rules as $rule) {
						$multiple[] = $__check($field, $rule);
					}
					return array_values(array_filter($multiple));
				} else if (!empty($rules['rule'])) {
					if (is_string($rules['rule'])) {
						$rule = $rules['rule'];
					} else {
						$rule = array_shift((array) $rules['rule']);
						$data += $rules['rule'];
					}
				}
			}

			if (Validator::invokeMethod($rule, $data) !== true) {
				if (is_string($rules)) {
					return $rules;
				}
				if (!empty($rules['message'])) {
					return $rules['message'];
				}
				return "{$field} is invalid.";
			}
			return null;
		};
		$errors = array();
		foreach ($rules as $field => $rules) {
			$errors[$field] = $__check($field, $rules);
		}
		return array_filter($errors);
	}

	/**
	 * Adds to or replaces built-in validation rules specified in `Validator::$_rules`.  Any new
	 * validation rules created are automatically callable as validation methods.
	 *
	 * For example:
	 * {{{
	 * Validator::add('zeroToNine', '/^[0-9]$/');
	 * $isValid = Validator::isZeroToNine("5"); // true
	 * $isValid = Validator::isZeroToNine("20"); // false
	 * }}}
	 *
	 * Alternatively, the first parameter may be an array of rules expressed as key/value pairs,
	 * as in the following:
	 * {{{
	 * Validator::add(array(
	 * 	'zeroToNine' => '/^[0-9]$/',
	 * 	'tenToNineteen' => '/^1[0-9]$/',
	 * ));
	 * }}}
	 *
	 * @param mixed $name The name of the validation rule (string), or an array of key/value pairs
	 *              of names and rules.
	 * @param string $rule If $name is a string, this should be a string regular expression, or a
	 *               closure that returns a boolean indicating success. Should be left blank if
	 *               `$name` is an array.
	 * @param array $options The default options for validating this rule. An option which applies
	 *              to all regular expression rules is `'contains'` which, if set to true, allows
	 *              validated values to simply _contain_ a match to a rule, rather than exactly
	 *              matching it in whole.
	 * @return void
	 */
	public static function add($name, $rule = null, $options = array()) {
		if (!is_array($name)) {
			$name = array($name => $rule);
		}
		static::$_rules = Set::merge(static::$_rules, $name);

		if (!empty($options)) {
			$options = array_combine(array_keys($name), array_fill(0, count($name), $options));
			static::$_options = Set::merge(static::$_options, $options);
		}
	}

	/**
	 * Adds, removes, or gets pre- or post-filters which are executed on a value before a validation
	 * is attempted, and after a validation succeeds, respectively. Each pre-filter (closure)
	 * transforms the value before it is passed on to the validation rule for checking. Each
	 * post-filter takes a value that has already passed validation, and performs additional
	 * validation on it.
	 *
	 * @param string $type Specifies which type of filter to work with, either `'before'` for
	 *               pre-filters, or `'after'` for post-filters.
	 * @param string $rule The name of the rule for which this filter will be added.  For example,
	 *        to add a filter for `Validator::isAlphaNumeric()`, use `'alphaNumeric'`.
	 * @param mixed $filter A closure which should accept 3 parameters:
	 *        - `$value`: The value to be validated.
	 *        - `$format`: The specific format of the validation rule.
	 *        - `$options`: An array of options specifying how the validation will be performed.
	 *        For pre-filters, `$filter` should return the newly-transformed value, which will be
	 *        checked against the validation rule. Null return values are ignored. True values
	 *        automatically succeed, and false values automatically fail. For post-filters,
	 *        `$filter` should return a boolean value, indicating whether the filter's additional
	 *        validation checking succeeded. If `$filter` is set to `false`, all filters assigned
	 *        to `$rule` (either pre or post, depending on `$type`) are removed.
	 * @return mixed If filter is null, returns an array containing all the filters assigned to
	 *         `$rule`.  Otherwise, returns null.
	 */
	public static function filter($type, $rule, $filter = null) {
		$types = array('before' => '_preFilters', 'after' => '_postFilters');
		if (!isset($types[$type])) {
			throw new InvalidArgumentException('Invalid filter type ' . $type);
		}
		$type = $types[$type];

		if (!isset(static::${$type}[$rule])) {
			static::${$type}[$rule] = array();
		}
		if (is_null($filter)) {
			return static::${$type}[$rule];
		}
		if ($filter === false) {
			static::${$type}[$rule] = array();
			return;
		}
		static::${$type}[$rule][] = $filter;
	}

	/**
	 * Checks a single value against a single validation rule in one or more formats.
	 *
	 * @param string $rule
	 * @param mixed $value
	 * @param string $format
	 * @param string $options
	 * @return boolean
	 * @todo Write tests for pre- and post-filtering
	 */
	public static function rule($rule, $value, $format = 'any', $options = array()) {
		if (!isset(static::$_rules[$rule])) {
			throw new InvalidArgumentException("Rule '{$rule}' is not a validation rule");
		}
		$defaults = isset(static::$_options[$rule]) ? static::$_options[$rule] : array();
		$options +=  $defaults + static::$_options['defaults'];
		$result = static::_filters('before', $rule, compact('value', 'format', 'options'));

		if ($result === true || $result === false) {
			return $result;
		}
		$value = is_null($result) ? $value : $result;

		$ruleCheck = static::$_rules[$rule];
		$ruleCheck = is_array($ruleCheck) ? $ruleCheck : array($ruleCheck);

		if (!$options['contains'] && !empty($ruleCheck)) {
			$append = function($item) { return is_string($item) ? '/^' . $item . '$/' : $item; };
			$ruleCheck = array_map($append, $ruleCheck);
		}

		if (in_array($format, array(null, 'all', 'any'))) {
			$formats = array_keys($ruleCheck);
			$all = ($format == 'all');
		} else {
			$formats = (array)$format;
			$all = true;
		}
		if (static::_checkFormats($ruleCheck, $formats, $value, $all, $options)) {
			return (bool)static::_filters('after', $rule, compact('value', 'format', 'options'));
		}
		return false;
	}

	/**
	 * Runs pre- or post-filters for a given rule and returns the result.
	 *
	 * If a pre-filter returns true or false, the validation immediately succeeds.  If a pre-filter
	 * returns null, validation continues.  If a pre-filter returns any other value, the value to be
	 * validated is modified, and all subsequent filters and validation rules will run against this
	 * new value.
	 *
	 * If a post-filter returns any true value, validation succeeds, or continues to the next
	 * filter.  If a post-filter returns any false value, validation immediately fails.
	 *
	 * Both pre- and post-filters take the same 3 parameters:
	 * - `$value`: The value to be validated.
	 * - `$format`: The format or list of formats against which this rule is being validated,
	 *   or null, if the rule is not format-dependent.
	 * - `$options`: Any other options associated with the rule.
	 *
	 * @param string $type Either 'before' or 'after', that indicate which filters to run.
	 * @param string $rule The name of the rule to run the filters for.
	 * @param string $params An array containing `'value'`, `'format'` and `'options'` keys (in
	 *               that order), corresponding to the parameters required by the filters.
	 * @return void
	 */
	protected static function _filters($type, $rule, $params) {
		$types = array('before' => '_preFilters', 'after' => '_postFilters');
		$var = $types[$type];

		if (!isset(static::${$var}[$rule])) {
			return ($type == 'after') ? true : null;
		}
		list($value, $format, $options) = array_values($params);

		foreach (static::${$var}[$rule] as $filter) {
			$result = $filter($value, $format, $options);

			if ($type == 'before') {
				if ($result === true || $result === false) {
					return $result;
				}
				$value = is_null($result) ? $value : $result;
			} else {
				if (!$result) {
					return false;
				}
			}
		}
		return ($type == 'before') ? $value : true;
	}

	/**
	 * Perform validation checks against a value using an array of all possible formats for a rule,
	 * and an array specifying which formats within the rule to use.
	 *
	 * @param array $rules All available rules.
	 * @param array $formats The list of rules to check against.
	 * @param mixed $value The value to perform validation on.
	 * @param boolean $all Whether all rule formats should be validated against.  If true, only
	 *                return successfully if _all_ formats validate, otherwise, returns true if
	 *                _any_ validates.
	 * @param array $options Validation options to be passed to rules defined as closures.
	 * @return boolean Returns true if the rule validation succeeded, otherwise false.
	 * @todo Add exception handling
	 */
	protected static function _checkFormats($rules, $formats, $value, $all, $options) {
		$success = false;

		foreach ($formats as $name) {
			if (!isset($rules[$name])) {
				// throw some kind of error here
				continue;
			}
			$check = $rules[$name];

			$regexPassed = (is_string($check) && preg_match($check, $value));
			$closurePassed = (is_object($check) && $check($value, $name, $options));

			if (!$all && ($regexPassed || $closurePassed)) {
				return true;
			}
			if ($all && (!$regexPassed && !$closurePassed)) {
				return false;
			}
		}
		return $all;
	}

	/**
	 * Checks that a string contains something other than whitespace
	 *
	 * Returns true if string contains something other than whitespace
	 *
	 * $value can be passed as an array:
	 * array('check' => 'valueToCheck');
	 *
	 * @param mixed $value Value to check
	 * @return boolean Success
	 */
	// public static function isNotEmpty($value) {}

	/**
	 * Checks that a string contains only integer or letters
	 *
	 * Returns true if string contains only integer or letters
	 *
	 * $value can be passed as an array:
	 * array('check' => 'valueToCheck');
	 *
	 * @param mixed $value Value to check
	 * @return boolean Success
	 */
	// public static function isAlphaNumeric($value) {}

	/**
	 * Checks that a string length is within s specified range.
	 * Spaces are included in the character count.
	 * Returns true is string matches value min, max, or between min and max,
	 *
	 * @param string $value Value to check for length
	 * @param integer $min Minimum value in range (inclusive)
	 * @param integer $max Maximum value in range (inclusive)
	 * @return boolean Success
	 */
	// public static function isLengthBetween($value, $min, $max) {}

	/**
	 * Returns true if field is left blank **OR** only whitespace characters are present in its
	 * value.  Whitespace characters include spaces, tabs, carriage returns and newlines.
	 *
	 * $value can be passed as an array:
	 * array('check' => 'valueToCheck');
	 *
	 * @param mixed $value Value to check
	 * @return boolean Success
	 */
	// public static function isBlank($value) {}

	/**
	 * Validates credit card numbers. Returns true if `$value` is in the proper credit card format.
	 *
	 * @param mixed $value credit card number to validate
	 * @param mixed $type 'all' may be passed as a sting, defaults to fast which checks format of
	 *                     most major credit cards if an array is used only the values of the array
	 *                     are checked.  Example: array('amex', 'bankcard', 'maestro')
	 * @param boolean $deep set to true this will check the Luhn algorithm of the credit card.
	 * @return boolean Success
	 * @see lithium\util\Validator::isLuhn()
	 */
	// public static function isCreditCard($value, $format = 'fast', $deep = false) {}

	/**
	 * Used to compare 2 numeric values.
	 *
	 * @param mixed $value If string is passed for a string must also be passed for $value2
	 *              used as an array it must be passed as
	 *              {{{array('check1' => value, 'operator' => 'value', 'check2' => value)}}}
	 * @param string $operator Can be either a word or operand
	 *               - is greater >, is less <, greater or equal >=
	 *               - less or equal <=, is less <, equal to ==, not equal !=
	 * @param integer $value2 only needed if $value1 is a string
	 * @return boolean Success
	 */
	public static function compare($value, $operator = null, $value2 = null) {
		if (is_array($value)) {
			extract($value, EXTR_OVERWRITE);
		}
		$replace = array(' ', "\t", "\n", "\r", "\0", "\x0B");
		$operator = str_replace($replace, '', strtolower($operator));

		$values = array(
			'>'   => ($value1 > $value2),
			'<'   => ($value1 < $value2),
			'>='  => ($value1 >= $value2),
			'<='  => ($value1 <= $value2),
			'=='  => ($value1 == $value2),
			'!='  => ($value1 != $value2),
			'===' => ($value1 === $value2)
		);

		if (isset($values[$operator])) {
			return $values[$operator];
		}
		return false;
	}

	/**
	 * Date validation, determines if the string passed is a valid date.
	 * keys that expect full month, day and year will validate leap years
	 *
	 * @param string $value a valid date string
	 * @param mixed $format Use a string or an array of the keys below. Arrays should be passed
	 * as array('dmy', 'mdy', etc). Possible values are:
	 *    - dmy 27-12-2006 or 27-12-06 separators can be a space, period, dash, forward slash
	 *    - mdy 12-27-2006 or 12-27-06 separators can be a space, period, dash, forward slash
	 *    - ymd 2006-12-27 or 06-12-27 separators can be a space, period, dash, forward slash
	 *    - dMy 27 December 2006 or 27 Dec 2006
	 *    - Mdy December 27, 2006 or Dec 27, 2006 comma is optional
	 *    - My December 2006 or Dec 2006
	 *    - my 12/2006 separators can be a space, period, dash, forward slash
	 * @return boolean Success
	 */
	// public static function date($value, $format = 'ymd') {}

	/**
	 * Time validation, determines if the string passed is a valid time.
	 * Validates time as 24hr (HH:MM) or am/pm ([H]H:MM[a|p]m)
	 * Does not allow/validate seconds.
	 *
	 * @param string $value a valid time string
	 * @return boolean Success
	 */
	// public static function time($value) {}

	/**
	 * Boolean validation, determines if value passed is a boolean integer or true/false.
	 *
	 * @param string $value a valid boolean
	 * @return boolean Success
	 */
	// public static function isBoolean($value) {}

	/**
	 * Checks that a value is a valid decimal. If $places is null, the $value is allowed to be a
	 * scientific float.  If no decimal point is found a false will be returned. Both the sign
	 * and exponent are optional.
	 *
	 * @param integer $value The value the test for decimal
	 * @param integer $precision if set $value value must have exactly $places after the decimal
	 *                point
	 * @return boolean Success
	 */
	// public static function isDecimal($value, $format = null) {}

	/**
	 * Validates for an email address.
	 *
	 * @param string $value Value to check
	 * @param boolean $deep Perform a deeper validation (if true), by also checking availability
	 *                of host
	 * @return boolean Success
	 */
	// public static function isEmail($value, $deep = false) {}

	/**
	 * Validates IPv4 addresses.
	 *
	 * @param string $value The string to test.
	 * @return boolean Success
	 */
	// public static function isIp($value) {}

	/**
	 * Checks whether the length of a string is greater or equal to a minimal length.
	 *
	 * @param string $value The string to test
	 * @param integer $min The minimal string length
	 * @return boolean Success
	 */
	public static function hasMinLength($value, $min) {
		return (strlen($value) >= $min);
	}

	/**
	 * Checks whether the length of a string is smaller or equal to a maximal length..
	 *
	 * @param string $value The string to test
	 * @param integer $max The maximal string length
	 * @return boolean Success
	 */
	public static function hasMaxLength($value, $max) {
		return (strlen($value) <= $max);
	}

	/**
	 * Checks that a value is a monetary amount.
	 *
	 * @param string $value Value to check
	 * @param string $symbolPosition Where symbol is located (left/right)
	 * @return boolean Success
	 */
	public static function isMoney($value, $format = 'left') {
		return static::_rule($value, __METHOD__, $format);
	}

	/**
	 * Validate a multiple select.
	 *
	 * @param mixed $value Value to check
	 * @param mixed $options Options for the check.
	 * 	Valid options
	 *	  in => provide a list of choices that selections must be made from
	 *	  max => maximun number of non-zero choices that can be made
	 * 	  min => minimum number of non-zero choices that can be made
	 * @return boolean Success
	 */
	public static function multiple($value, $options = array()) {
		$defaults = array('in' => null, 'max' => null, 'min' => null);
		$options += $defaults;
		$value = array_filter((array)$value);

		if (empty($value)) {
			return false;
		}
		if ($options['max'] && sizeof($value) > $options['max']) {
			return false;
		}
		if ($options['min'] && sizeof($value) < $options['min']) {
			return false;
		}
		if ($options['in'] && is_array($options['in'])) {
			foreach ($value as $val) {
				if (!in_array($val, $options['in'])) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Checks if a value is numeric.
	 *
	 * @param string $value Value to check
	 * @return boolean Success
	 */
	// public static function isNumeric($value) {}

	/**
	 * Check that a value is a valid phone number.
	 *
	 * @param mixed $value Value to check (string or array)
	 * @param string $regex Regular expression to use
	 * @param string $country Country code (defaults to 'all')
	 * @return boolean Success
	 */
	//public static function isPhone($value, $format = 'any') {}

	/**
	 * Checks that a given value is a valid postal code.
	 *
	 * @param mixed $value Value to check
	 * @param string $regex Regular expression to use
	 * @param string $country Country to use for formatting
	 * @return boolean Success
	 */
	// public static function isPostalCode($value, $country = null) {}

	/**
	 * Validate that a number is in specified range.
	 * if $lower and $upper are not set, will return true if
	 * $value is a legal finite on this platform
	 *
	 * @param string $value Value to check
	 * @param integer $lower Lower limit
	 * @param integer $upper Upper limit
	 * @return boolean Success
	 */
	public static function isInRange($value, $lower = null, $upper = null) {
		if (!is_numeric($value)) {
			return false;
		}
		if (isset($lower) && isset($upper)) {
			return ($value > $lower && $value < $upper);
		}
		return is_finite($value);
	}

	/**
	 * Checks that a value is a valid Social Security Number.
	 *
	 * @param mixed $value Value to check
	 * @param string $regex Regular expression to use
	 * @param string $country Country
	 * @return boolean Success
	 */
	// public static function isSsn($value, $format = null) {}

	/**
	 * Checks that a value is a valid URL according to http://www.w3.org/Addressing/URL/url-spec.txt
	 *
	 * The regex checks for the following component parts:
	 * 	a valid, optional, scheme
	 * 		a valid ip address OR
	 * 		a valid domain name as defined by section 2.3.1 of http://www.ietf.org/rfc/rfc1035.txt
	 *	  with an optional port number
	 *	an optional valid path
	 *	an optional query string (get parameters)
	 *	an optional fragment (anchor tag)
	 *
	 * @param string $value Value to check
	 * @return boolean Success
	 */
	// public static function url($value, $strict = false) {}

	/**
	 * Luhn algorithm
	 *
	 * Checks that a credit card number is a valid Luhn sequence.
	 *
	 * @param mixed $value A string or integer representing a credit card number.
	 * @link http://en.wikipedia.org/wiki/Luhn_algorithm
	 * @return boolean Success
	 */
	// public static function isLuhn($value) {}

	/**
	 * Checks if a value is in a given list.
	 *
	 * @param string $value Value to check
	 * @param array $list List to check against
	 * @return boolean Success
	 */
	// public static function isInList($value, $list) {}
}

?>