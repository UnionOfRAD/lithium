<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util;

use \lithium\util\Set;
use \InvalidArgumentException;

/**
 * The `Validator` class provies static access to commonly used data validation logic. These common
 * routines cover HTML form input data such as phone and credit card numbers, dates and postal
 * codes, but also include general checks for regular expressions and booleans and numericality.
 *
 * General data checking is done by using `Validator` statically. Rules can be specified as a
 * parameter to the `rule()` method or automatically accessed via the `is[RuleName]()` method name
 * convention:
 *
 * {{{
 * 		use \lithium\util\Validator;
 *
 * 		// The following are equivalent:
 * 		Validator::rule('email', 'foo@example.com');  // true
 * 		Validator::isEmail('foo-at-example.com');     // false
 * }}}
 *
 * Data can also be validated against multiple rules, each having their own associated error
 * message. The rule structure is array-based and hierarchical based on rule names and
 * messages. Resposes match the keys present in `$data` up with an array of rules which they
 * violate.
 *
 * {{{ embed:lithium\tests\cases\util\ValidatorTest::testCheckMultipleHasFirstError(1-15) }}}
 *
 * See the `check()` method for more information an multi-value datasets. Custom validation rules
 * can also be added to `Validator` at runtime. These can either take the form of regular expression
 * strings or functions supplied to the `add()` method.
 */
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
	 * Default options used when defining a new validator rule.
	 *
	 * @var array Options
	 * @see lithium\util\Validator::add()
	 * @see lithium\util\Validator::rule()
	 */
	protected static $_options = array(
		'defaults' => array('contains' => true)
	);

	/**
	 * Initializes the list of default validation rules.
	 *
	 * @return void
	 */
	public static function __init() {
		$alnum = '[A-Fa-f0-9]';
		$class = get_called_class();
		static::$_methodFilters[$class] = array();

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
			'ip' => function($value, $format = null, array $options = array()) {
				$options += array('flags' => array());
				return (boolean) filter_var(
					$value, FILTER_VALIDATE_IP, array('flags' => $options['flags'])
				);
			},
			'money'        => array(
				'right'    => '/^(?!0,?\d)(?:\d{1,3}(?:([, .])\d{3})?(?:\1\d{3})*|(?:\d+))' .
				              '((?!\1)[,.]\d{2})?(?<!\x{00a2})\p{Sc}?$/u',
				'left'     => '/^(?!\x{00a2})\p{Sc}?(?!0,?\d)(?:\d{1,3}(?:([, .])\d{3})?' .
				              '(?:\1\d{3})*|(?:\d+))((?!\1)[,.]\d{2})?$/u'
			),
			'notEmpty'     => '/[^\s]+/m',
			'phone'        => '/^\+?[0-9\(\)\-]{10,20}$/',
			'postalCode'   => '/(^|\A\b)[A-Z0-9\s\-]{5,}($|\b\z)/i',
			'regex'        => '/^\/(.+)\/[gimsxu]*$/',
			'time'         => '%^((0?[1-9]|1[012])(:[0-5]\d){0,2}([AP]M|[ap]m))$|^([01]\d|2[0-3])' .
			                  '(:[0-5]\d){0,2}$%',
			'boolean' => function($value) {
				$bool = is_bool($value);
				$filter = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

				return ($bool || $filter !== null);
			},
			'decimal' => function($value, $format = null, array $options = array()) {
				if (isset($options['precision'])) {
					$precision = strlen($value) - strrpos($value, '.') - 1;

					if ($precision !== (int) $options['precision']) {
						return false;
					}
				}
				return (boolean) filter_var($value, FILTER_VALIDATE_FLOAT);
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
			'inRange' => function($value, $format, $options) {
				$defaults = array('upper' => null, 'lower' => null);
				$options += $defaults;

				if (!is_numeric($value)) {
					return false;
				}
				switch (true) {
					case (!is_null($options['upper']) && !is_null($options['lower'])):
						return ($value > $options['lower'] && $value < $options['upper']);
					case (!is_null($options['upper'])):
						return ($value < $options['upper']);
					case (!is_null($options['lower'])):
						return ($value > $options['lower']);
				}
				return is_finite($value);
			},
			'uuid' => "/{$alnum}{8}-{$alnum}{4}-{$alnum}{4}-{$alnum}{4}-{$alnum}{12}/",
			'email' => function($value) {
				return filter_var($value, FILTER_VALIDATE_EMAIL);
			},
			'url' => function($value, $format = null, array $options = array()) {
				$options += array('flags' => array());
				return (boolean) filter_var(
					$value, FILTER_VALIDATE_URL, array('flags' => $options['flags'])
				);
			}
		);

		$emptyCheck = function($self, $params, $chain) {
			extract($params);
			return (empty($value) && $value != '0') ? false : $chain->next($self, $params, $chain);
		};

		static::$_methodFilters[$class]['alphaNumeric'] = array($emptyCheck);
		static::$_methodFilters[$class]['notEmpty'] = array($emptyCheck);

		static::$_methodFilters[$class]['creditCard'] = array(function($self, $params, $chain) {
			extract($params);
			$options += array('deep' => false);

			if (strlen($value = str_replace(array('-', ' '), '', $value)) < 13) {
				return false;
			}
			if (!$chain->next($self, compact('value') + $params, $chain)) {
				return false;
			}
			return $options['deep'] ? Validator::isLuhn($value) : true;
		});

		static::$_methodFilters[$class]['email'] = array(
			function($self, $params, $chain) {
				extract($params);
				$defaults = array('deep' => false);
				$options += $defaults;

				if (!$chain->next($self, $params, $chain)) {
					return false;
				}
				if (!$options['deep']) {
					return true;
				}
				list($prefix, $host) = explode('@', $params['value']);

				if (getmxrr($host, $mxhosts)) {
					return is_array($mxhosts);
				}
				return false;
			}
		);
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
	 * Checks a set of values against a specified rules list. This method may be used to validate
	 * any arbitrary array of data against a set of validation rules.
	 *
	 * @param array $values An array of key/value pairs, where the values are to be checked.
	 * @param array $rules An array of rules to check the values in `$values` against. Each key in
	 *              `$rules` should match a key contained in `$values`, and each value should be a
	 *              validation rule in one of the allowable formats. For example, if you are
	 *              validating a data set containing a `'title'` key, possible values for
	 *              `$rules` would be as follows:
	 *              - `array('title' => 'You must include a title')`: This is the simplest form of
	 *                validation rule, in which the value is simply a message to display if the rule
	 *                fails. Using this format, all other validation settings inherit from the
	 *                defaults, including the validation rule itself, which only checks to see that
	 *                the corresponding key in `$values` is present and contains a value that is not
	 *                empty. _Please note when globalizing validation messages:_ When specifying
	 *                messages, it may be preferable to use a code string (i.e. `'ERR_NO_TITLE'`)
	 *                instead of the full text of the validation error. These code strings may then
	 *                be translated by the appropriate tools in the templating layer.
	 *              - `array('title' => array('alphaNumeric', 'message' => 'Invalid title'))`: In
	 *                the second format, the validation rule and associated configuration are
	 *                specified as an array, where the rule to use is the first value in the array
	 *                (no key), and additional settings are specified as other keys in the array.
	 *                Please see the list below for more information on allowed keys.
	 *              - The final format allows you to apply multiple validation rules to a single
	 *                value, and it is specified as follows:
	 *
	 * `array('title' => array(
	 * 	array('notEmpty', 'message' => 'You must include a title'),
	 * 	array('alphaNumeric', 'message' => 'Your title must be alphanumeric')
	 * ));`
	 *
	 * Each rule defined as an array can contain any of the following settings (in addition to the
	 * first value, which represents the rule to be used):
	 *  - `'message'` _string_: The error message to be returned if the validation rule fails. See
	 *    the note above regarding globalization of error messages.
	 *  - `'required`' _boolean_: Represents whether the value is required to be present in
	 *    `$values`. If `'required'` is set to `false`, the validation rule will be skipped if the
	 *     corresponding key is not present. Defaults to `true`.
	 *  - `'skipEmpty'` _boolean_: Similar to `'required'`, this setting (if `true`) will cause the
	 *    validation rule to be skipped if the corresponding value is empty (an empty string or
	 *    `null`). Defaults to `false`.
	 *  - `'format'` _string_: If the validation rule has multiple format definitions (see the
	 *    `add()` or `__init()` methods), the name of the format to be used can be specified here.
	 *    Additionally, two special values can be used: either `'any'`, which means that all formats
	 *    will be checked and the rule will pass if any format passes, or `'all'`, which requires
	 *    all formats to pass in order for the rule check to succeed.
	 * @return array Returns an array containing all validation failures for data in `$values`,
	 *         where each key matches a key in `$values`, and each value is an array of that
	 *         element's validation errors.
	 */
	public static function check(array $values, array $rules, array $options = array()) {
		$defaults = array(
			'notEmpty',
			'message' => null,
			'required' => true,
			'skipEmpty' => false,
			'format' => 'any'
		);
		$errors = array();

		foreach ($rules as $field => $rules) {
			$rules = is_string($rules) ? array('message' => $rules) : $rules;
			$rules = is_array(current($rules)) ? $rules : array($rules);
			$errors[$field] = array();

			foreach ($rules as $key => $rule) {
				$rule += $defaults + compact('values');
				list($name) = $rule;

				if (!isset($values[$field])) {
					if ($rule['required']) {
						$errors[$field][] = $rule['message'] ?: $key;
					}
					continue;
				}
				if (empty($values[$field]) && $rule['skipEmpty']) {
					continue;
				}
				if (!static::rule($name, $values[$field], $rule['format'], $rule + $options)) {
					$errors[$field][] = $rule['message'] ?: $key;
				}
			}
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
	 * In addition to regular expressions, validation rules can also be defined as full anonymous
	 * functions:
	 * {{{
	 * use app\models\Account;
	 *
	 * Validator::add('accountActive', function($value) {
	 * 	$value = is_int($value) ? Account::find($value) : $value;
	 * 	return (boolean) $value->is_active;
	 * });
	 *
	 * $testAccount = Account::create(array('is_active' => false));
	 * Validator::isAccountActive($testAccount); // returns false
	 * }}}
	 *
	 * These functions can take up to 3 parameters:
	 * 	- `$value` _mixed_: This is the actual value to be validated (as in the above example).
	 * 	- `$format` _string_: Often, validation rules come in multiple "formats", for example:
	 * 	  postal codes, which vary by country or region. Defining multiple formats allows you to
	 * 	  retian flexibility in how you validate data. In cases where a user's country of origin is
	 * 	  known, the appropriate validation rule may be selected. In cases where it is not known,
	 * 	  the value of `$format` may be `'any'`, which should pass if any format matches. In cases
	 * 	  where validation rule formats are not mutually exclusive, the value may be `'all'`, in
	 * 	  which case all must match.
	 * 	- `$options` _array_: This parameter allows a validation rule to implement custom options.
	 *
	 * @see lithium\util\Validator::$_rules
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
	public static function add($name, $rule = null, array $options = array()) {
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
	 * Checks a single value against a single validation rule in one or more formats.
	 *
	 * @param string $rule
	 * @param mixed $value
	 * @param string $format
	 * @param string $options
	 * @return boolean
	 * @todo Write tests for pre- and post-filtering
	 */
	public static function rule($rule, $value, $format = 'any', array $options = array()) {
		if (!isset(static::$_rules[$rule])) {
			throw new InvalidArgumentException("Rule '{$rule}' is not a validation rule");
		}
		$defaults = isset(static::$_options[$rule]) ? static::$_options[$rule] : array();
		$options = (array) $options + $defaults + static::$_options['defaults'];

		$ruleCheck = static::$_rules[$rule];
		$ruleCheck = is_array($ruleCheck) ? $ruleCheck : array($ruleCheck);

		if (!$options['contains'] && !empty($ruleCheck)) {
			foreach ($ruleCheck as $key => $item) {
				$ruleCheck[$key] = is_string($item) ? "/^{$item}$/" : $item;
			}
		}

		$params = compact('value', 'format', 'options');
		return static::_filter($rule, $params, static::_checkFormats($ruleCheck));
	}

	/**
	 * Returns a list of available validation rules, or the configuration details of a single rule.
	 *
	 * @param string $name Optional name of a rule to get the details of. If not specified, an array
	 *               of all available rule names is returned. Otherwise, returns the details of a
	 *               single rule. This can be a regular expression string, a closure object, or an
	 *               array of available rule formats made up of string regular expressions,
	 *               closures, or both.
	 * @return mixed Returns either an single array of rule names, or the details of a single rule.
	 */
	public static function rules($name = null) {
		if (!$name) {
			return array_keys(static::$_rules);
		}
		return isset(static::$_rules[$name]) ? static::$_rules[$name] : null;
	}

	/**
	 * Perform validation checks against a value using an array of all possible formats for a rule,
	 * and an array specifying which formats within the rule to use.
	 *
	 * @param array $rules All available rules.
	 * @param array $formats The list of rules to check against.
	 * @param mixed $value The value to perform validation on.
	 * @param array $options Validation options to be passed to rules defined as closures.
	 *              - `'all'` _boolean_: Whether all rule formats should be validated against. If
	 *                `true`, only return successfully if _all_ formats validate, otherwise, returns
	 *                `true` if _any_ validates.
	 * @return boolean Returns true if the rule validation succeeded, otherwise false.
	 */
	protected static function _checkFormats($rules) {
		return function($self, $params, $chain) use ($rules) {
			extract($params);
			$defaults = array('all' => true);
			$options += $defaults;

			$formats = (array) $format;
			$success = false;

			if (in_array($format, array(null, 'all', 'any'))) {
				$formats = array_keys($rules);
				$options['all'] = ($format == 'all');
			}

			foreach ($formats as $name) {
				if (!isset($rules[$name])) {
					continue;
				}
				$check = $rules[$name];

				$regexPassed = (is_string($check) && preg_match($check, $value));
				$closurePassed = (is_object($check) && $check($value, $name, $options));

				if (!$options['all'] && ($regexPassed || $closurePassed)) {
					return true;
				}
				if ($options['all'] && (!$regexPassed && !$closurePassed)) {
					return false;
				}
			}
			return $options['all'];
		};
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
	 * @see lithium\util\Validator::isLuhn()
	 * @param mixed $value credit card number to validate
	 * @param mixed $type 'all' may be passed as a sting, defaults to fast which checks format of
	 *                     most major credit cards if an array is used only the values of the array
	 *                     are checked.  Example: array('amex', 'bankcard', 'maestro')
	 * @param boolean $deep set to true this will check the Luhn algorithm of the credit card.
	 * @return boolean Success
	 */
	// public static function isCreditCard($value, $format = 'fast', $deep = false) {}

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
	 * Checks that a value is a monetary amount.
	 *
	 * @param string $value Value to check
	 * @param string $format Where symbol is located (left/right)
	 * @return boolean Success
	 */
	// public static function isMoney($value, $format = 'left') {}

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
	// public static function isInRange($value, $lower = null, $upper = null) {}

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
	 * Checks that a value is a valid URL according to
	 * http://www.w3.org/Addressing/URL/url-spec.txt
	 *
	 * The regex checks for the following component parts:
	 * 	    - A valid, optional, scheme
	 * 		- A valid ip address OR
	 * 		- A valid domain name as defined by section 2.3.1 of
	 * 		  http://www.ietf.org/rfc/rfc1035.txt with an optional port number
	 *	    - An optional valid path
	 *	    - An optional query string (get parameters)
	 *	    - An optional fragment (anchor tag)
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