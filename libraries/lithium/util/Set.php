<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 *                Copyright 2009, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */

namespace lithium\util;

/**
 * Used for complex manipulation, comparison, and access of array data. Some methods allow for
 * XPath-like data access, as follows:
 * 
 *  - `'/User/id'`: Similar to the classic {n}.User.id.
 *  - `'/User[2]/name'`: Selects the name of the second User.
 *  - `'/User[id>2]'`: Selects all Users with an id > 2.
 *  - `'/User[id>2][<5]'`: Selects all Users with an id > 2 but < 5.
 *  - `'/Post/Comment[author_name=John]/../name'`: Selects the name of
 *    all posts that have at least one comment written by John.
 *  - `'/Posts[name]'`: Selects all Posts that have a `'name'` key.
 *  - `'/Comment/.[1]'`: Selects the contents of the first comment.
 *  - `'/Comment/.[:last]'`: Selects the last comment.
 *  - `'/Comment/.[:first]'`: Selects the first comment.
 *  - `'/Comment[text=/lithium/i]`': Selects the all comments that have
 *    a text matching the regex `/lithium/i`.
 *  - `'/Comment/@*'`: Selects all key names of all comments.
 */
class Set {

	/**
	 * Add the keys/values in `$array2` that are not found in `$array` onto the end of `$array`.
	 *
	 * @param mixed $array Original array.
	 * @param mixed $array2 Second array to add onto the Original.
	 * @return array A Blended array of keys and values.
	 */
	public static function blend($array, $array2) {
		if (empty($array) && !empty($array2)) {
			return $array2;
		}
		if (!empty($array) && !empty($array2)) {
			foreach ($array2 as $key => $value) {
				if (!isset($array[$key])) {
					$array[$key] = $value;
				} elseif (is_array($value)) {
					$array[$key] = static::blend($array[$key], $array2[$key]);
				}
			}
		}
		return $array;
	}

	/**
	 * Checks if a particular path is set in an array
	 *
	 * @param mixed $data Data to check on.
	 * @param mixed $path A dot-delimited string.
	 * @return boolean `true` if path is found, `false` otherwise.
	 */
	public static function check($data, $path = null) {
		if (empty($path)) {
			return $data;
		}
		if (!is_array($path)) {
			$path = explode('.', $path);
		}
		foreach ($path as $i => $key) {
			if (is_numeric($key) && intval($key) > 0 || $key === '0') {
				$key = intval($key);
			}
			if ($i === count($path) - 1) {
				return (is_array($data) && isset($data[$key]));
			} else {
				if (!is_array($data) || !isset($data[$key])) {
					return false;
				}
				$data =& $data[$key];
			}
		}
		return true;
	}

	/**
	 * Creates an associative array using a `$path1` as the path to build its keys, and optionally
	 * `$path2` as path to get the values. If `$path2` is not specified, all values will be
	 * initialized to `null` (useful for `Set::merge()`). You can optionally group the values by
	 * what is obtained when following the path specified in `$groupPath`.
	 *
	 * @param array $data Array from where to extract keys and values.
	 * @param mixed $path1 As an array, or as a dot-delimited string.
	 * @param mixed $path2 As an array, or as a dot-delimited string.
	 * @param string $groupPath As an array, or as a dot-delimited string.
	 * @return array Combined array.
	 */
	public static function combine($data, $path1 = null, $path2 = null, $groupPath = null) {
		if (empty($data)) {
			return array();
		}
		if (is_object($data)) {
			$data = get_object_vars($data);
		}
		if (is_array($path1)) {
			$format = array_shift($path1);
			$keys = static::format($data, $format, $path1);
		} else {
			$keys = static::extract($data, $path1);
		}
		$vals = array();
		if (!empty($path2) && is_array($path2)) {
			$format = array_shift($path2);
			$vals = static::format($data, $format, $path2);
		} elseif (!empty($path2)) {
			$vals = static::extract($data, $path2);
		}
		$valCount = count($vals);
		$count = count($keys);

		for ($i = $valCount; $i < $count; $i++) {
			$vals[$i] = null;
		}
		if ($groupPath != null) {
			$group = static::extract($data, $groupPath);
			if (!empty($group)) {
				$c = count($keys);
				for ($i = 0; $i < $c; $i++) {
					if (!isset($group[$i])) {
						$group[$i] = 0;
					}
					if (!isset($out[$group[$i]])) {
						$out[$group[$i]] = array();
					}
					$out[$group[$i]][$keys[$i]] = $vals[$i];
				}
				return $out;
			}
		}
		return array_combine($keys, $vals);
	}

	/**
	 * Determines if `val2` is contained in `val1`
	 *
	 * @param array $val1 First value.
	 * @param array $val2 Second value.
	 * @return boolean true if `$val1` contains `$val2`, `false` otherwise.
	 */
	public static function contains($val1, $val2) {
		if (empty($val1) || empty($val2)) {
			return false;
		}
		foreach ((array) $val2 as $key => $val) {
			if (is_numeric($key)) {
				static::contains($val, $val1);
			} elseif (!isset($val1[$key]) || $val1[$key] != $val) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Counts the dimensions of an array. If `$all` is set to `false` (which is the default) it will
	 * only consider the dimension of the first element in the array.
	 *
	 * @param array $data Array to count dimensions on.
	 * @param array $options
	 * @param integer $count Start the depth count at this number.
	 * @return integer The number of dimensions in `$array`.
	 */
	public static function depth($data, $options = array(), $count = 0) {
		if (empty($data)) {
			return 0;
		}
		if (!is_array($options)) {
			$options = array('all' => $options, 'count' => $count);
		}
		$defaults = array('all' => false, 'count' => 0);
		$options += $defaults;

		if (!empty($options['all'])) {
			$depth = array($options['count']);
			if (is_array($data) && reset($data) !== false) {
				foreach ($data as $value) {
					$depth[] = static::depth($value, array(
						'all' => $options['all'],
						'count' => $options['count'] + 1
					));
				}
			}
			return max($depth);
		}
		if (is_array(reset($data))) {
			return static::depth(reset($data)) + 1;
		}
		return 1;
	}

	/**
	 * Computes the difference between two arrays.
	 *
	 * @param mixed $val1 First value.
	 * @param mixed $val2 Second value.
	 * @return array Computed difference.
	 */
	public static function diff($val1, $val2 = null) {
		if (empty($val1)) {
			return (array) $val2;
		} elseif (empty($val2)) {
			return (array) $val1;
		}
		$out = array();

		foreach ($val1 as $key => $val) {
			$exists = isset($val2[$key]);

			if ($exists && $val2[$key] != $val) {
				$out[$key] = $val;
			} elseif (!$exists) {
				$out[$key] = $val;
			}
			unset($val2[$key]);
		}

		foreach ($val2 as $key => $val) {
			if (!isset($out[$key])) {
				$out[$key] = $val;
			}
		}
		return $out;
	}

	/**
	 * Implements partial support for XPath 2.0.
	 *
	 * @param array $data An array of data to extract from.
	 * @param string $path An absolute XPath 2.0 path. Only absolute paths starting with a
	 *               single slash are supported right now. Implemented selectors:
	 *               - `'/User/id'`: Similar to the classic {n}.User.id.
	 *               - `'/User[2]/name'`: Selects the name of the second User.
	 *               - `'/User[id>2]'`: Selects all Users with an id > 2.
	 *               - `'/User[id>2][<5]'`: Selects all Users with an id > 2 but < 5.
	 *               - `'/Post/Comment[author_name=John]/../name'`: Selects the name of
	 *                 all posts that have at least one comment written by John.
	 *               - `'/Posts[name]'`: Selects all Posts that have a `'name'` key.
	 *               - `'/Comment/.[1]'`: Selects the contents of the first comment.
	 *               - `'/Comment/.[:last]'`: Selects the last comment.
	 *               - `'/Comment/.[:first]'`: Selects the first comment.
	 *               - `'/Comment[text=/lithium/i]`': Selects the all comments that have
	 *                 a text matching the regex `/lithium/i`.
	 *               - `'/Comment/@*'`: Selects all key names of all comments.
	 * @param array $options Currently only supports `'flatten'` which can be
	 *              disabled for higher XPath-ness.
	 * @return array An array of matched items.
	 */
	public static function extract($data, $path = null, array $options = array()) {
		if (empty($data)) {
			return array();
		}
		if (is_string($data)) {
			$tmp = $path;
			$path = $data;
			$data = $tmp;
			unset($tmp);
		}
		if ($path === '/') {
			return array_filter($data, function($data) {
				return ($data === 0 || $data === '0' || !empty($data));
			});
		}
		$contexts = $data;
		$options = array_merge(array('flatten' => true), $options);

		if (!isset($contexts[0])) {
			$contexts = array($data);
		}
		$tokens = array_slice(preg_split('/(?<!=)\/(?![a-z-]*\])/', $path), 1);

		do {
			$token = array_shift($tokens);
			$conditions = false;
			if (preg_match_all('/\[([^=]+=\/[^\/]+\/|[^\]]+)\]/', $token, $m)) {
				$conditions = $m[1];
				$token = substr($token, 0, strpos($token, '['));
			}
			$matches = array();
			foreach ($contexts as $key => $context) {
				if (!isset($context['trace'])) {
					$context = array('trace' => array(null), 'item' => $context, 'key' => $key);
				}
				if ($token === '..') {
					if (count($context['trace']) == 1) {
						$context['trace'][] = $context['key'];
					}
					$parent = join('/', $context['trace']) . '/.';
					$context['item'] = static::extract($parent, $data);
					$context['key'] = array_pop($context['trace']);
					if (isset($context['trace'][1]) && $context['trace'][1] > 0) {
						$context['item'] = $context['item'][0];
					} elseif (!empty($context['item'][$key])) {
						$context['item'] = $context['item'][$key];
					} else {
						$context['item'] = array_shift($context['item']);
					}
					$matches[] = $context;
					continue;
				}
				$match = false;
				if ($token === '@*' && is_array($context['item'])) {
					$matches[] = array(
						'trace' => array_merge($context['trace'], (array) $key),
						'key' => $key,
						'item' => array_keys($context['item']),
					);
				} elseif (is_array($context['item']) && isset($context['item'][$token])) {
					$items = $context['item'][$token];
					if (!is_array($items)) {
						$items = array($items);
					} elseif (!isset($items[0])) {
						$current = current($items);
						if ((is_array($current) && count($items) <= 1) || !is_array($current)) {
							$items = array($items);
						}
					}

					foreach ($items as $key => $item) {
						$ctext = array($context['key']);
						if (!is_numeric($key)) {
							$ctext[] = $token;
							$token = array_shift($tokens);
							if (isset($items[$token])) {
								$ctext[] = $token;
								$item = $items[$token];
								$matches[] = array(
									'trace' => array_merge($context['trace'], $ctext),
									'key' => $key,
									'item' => $item,
								);
								break;
							} else {
								array_unshift($tokens, $token);
							}
						} else {
							$key = $token;
						}

						$matches[] = array(
							'trace' => array_merge($context['trace'], $ctext),
							'key' => $key,
							'item' => $item,
						);
					}
				} elseif (($key === $token || (ctype_digit($token) && $key == $token) || $token === '.')) {
					$context['trace'][] = $key;
					$matches[] = array(
						'trace' => $context['trace'],
						'key' => $key,
						'item' => $context['item'],
					);
				}
			}
			if ($conditions) {
				foreach ($conditions as $condition) {
					$filtered = array();
					$length = count($matches);
					foreach ($matches as $i => $match) {
						if (static::matches(array($condition), $match['item'], $i + 1, $length)) {
							$filtered[] = $match;
						}
					}
					$matches = $filtered;
				}
			}
			$contexts = $matches;

			if (empty($tokens)) {
				break;
			}
		} while (1);

		$r = array();

		foreach ($matches as $match) {
			if ((!$options['flatten'] || is_array($match['item'])) && !is_int($match['key'])) {
				$r[] = array($match['key'] => $match['item']);
			} else {
				$r[] = $match['item'];
			}
		}
		return $r;
	}

	/**
	 * Collapses a multi-dimensional array into a single dimension, using a delimited array path
	 * for each array element's key, i.e. array(array('Foo' => array('Bar' => 'Far'))) becomes
	 * array('0.Foo.Bar' => 'Far').
	 *
	 * @param array $data array to flatten
	 * @param array $options Available options are:
	 *              - `'separator'`: String to separate array keys in path (defaults to `'.'`).
	 *              - `'path'`: Starting point (defaults to null).
	 * @return array
	 */
	public static function flatten($data, $options = array()) {
		$result = array();

		if (!is_array($options)) {
			$options = array('separator' => $options);
		}
		$defaults = array('separator' => '.', 'path' => null);
		$options += $defaults;

		if (!is_null($options['path'])) {
			$options['path'] .= $options['separator'];
		}
		foreach ($data as $key => $val) {
			if (is_array($val)) {
				$result += (array) static::flatten($val, array(
					'separator' => $options['separator'],
					'path' => $options['path'] . $key
				));
			} else {
				$result[$options['path'] . $key] = $val;
			}
		}
		return $result;
	}

	/**
	 * Returns a series of values extracted from an array, formatted in a format string.
	 *
	 * @param array $data Source array from which to extract the data.
	 * @param string $format Format string into which values will be inserted using `sprintf()`.
	 * @param array $keys An array containing one or more `Set::extract()`-style key paths.
	 * @return array An array of strings extracted from `$keys` and formatted with `$format`.
	 * @link http://php.net/sprintf
	 */
	public static function format($data, $format, $keys) {
		$extracted = array();
		$count = count($keys);

		if (!$count) {
			return;
		}
		for ($i = 0; $i < $count; $i++) {
			$extracted[] = static::extract($data, $keys[$i]);
		}
		$out = array();
		$data = $extracted;
		$count = count($data[0]);

		if (preg_match_all('/\{([0-9]+)\}/msi', $format, $keys2) && isset($keys2[1])) {
			$keys = $keys2[1];
			$format = preg_split('/\{([0-9]+)\}/msi', $format);
			$count2 = count($format);

			for ($j = 0; $j < $count; $j++) {
				$formatted = '';
				for ($i = 0; $i <= $count2; $i++) {
					if (isset($format[$i])) {
						$formatted .= $format[$i];
					}
					if (isset($keys[$i]) && isset($data[$keys[$i]][$j])) {
						$formatted .= $data[$keys[$i]][$j];
					}
				}
				$out[] = $formatted;
			}
		} else {
			$count2 = count($data);
			for ($j = 0; $j < $count; $j++) {
				$args = array();
				for ($i = 0; $i < $count2; $i++) {
					if (isset($data[$i][$j])) {
						$args[] = $data[$i][$j];
					}
				}
				$out[] = vsprintf($format, $args);
			}
		}
		return $out;
	}

	/**
	 * Inserts `$data` into an array as defined by `$path`.
	 *
	 * @param mixed $list Where to insert into.
	 * @param mixed $path A dot-delimited string.
	 * @param array $data Data to insert.
	 * @return array
	 */
	public static function insert($list, $path, $data = null) {
		if (!is_array($path)) {
			$path = explode('.', $path);
		}
		$_list =& $list;

		foreach ($path as $i => $key) {
			if (is_numeric($key) && intval($key) > 0 || $key === '0') {
				$key = intval($key);
			}
			if ($i === count($path) - 1) {
				$_list[$key] = $data;
			} else {
				if (!isset($_list[$key])) {
					$_list[$key] = array();
				}
				$_list =& $_list[$key];
			}
		}
		return $list;
	}

	/**
	 * Checks to see if all the values in the array are numeric.
	 *
	 * @param array $array The array to check.  If null, the value of the current Set object.
	 * @return boolean `true` if values are numeric, `false` otherwise.
	 */
	public static function isNumeric($array = null) {
		if (empty($array)) {
			return null;
		}
		if ($array === range(0, count($array) - 1)) {
			return true;
		}
		$numeric = true;
		$keys = array_keys($array);
		$count = count($keys);

		for ($i = 0; $i < $count; $i++) {
			if (!is_numeric($array[$keys[$i]])) {
				$numeric = false;
				break;
			}
		}
		return $numeric;
	}

	/**
	 * This function can be used to see if a single item or a given XPath
	 * match certain conditions.
	 *
	 * @param mixed $conditions An array of condition strings or an XPath expression.
	 * @param array $data An array of data to execute the match on.
	 * @param integer $i Optional: The 'nth'-number of the item being matched.
	 * @param integer $length
	 * @return boolean
	 */
	public static function matches($conditions, $data = array(), $i = null, $length = null) {
		if (empty($conditions)) {
			return true;
		}
		if (is_string($conditions) || is_string($data)) {
			return !!static::extract($data, $conditions);
		}
		foreach ($conditions as $condition) {
			if ($condition === ':last') {
				if ($i != $length) {
					return false;
				}
				continue;
			} elseif ($condition === ':first') {
				if ($i != 1) {
					return false;
				}
				continue;
			}
			if (!preg_match('/(.+?)([><!]?[=]|[><])(.*)/', $condition, $match)) {
				if (ctype_digit($condition)) {
					if ($i != $condition) {
						return false;
					}
				} elseif (preg_match_all('/(?:^[0-9]+|(?<=,)[0-9]+)/', $condition, $matches)) {
					return in_array($i, $matches[0]);
				} elseif (!isset($data[$condition])) {
					return false;
				}
				continue;
			}
			list(,$key,$op,$expected) = $match;

			if (!isset($data[$key])) {
				return false;
			}
			$val = $data[$key];

			if ($op === '=' && $expected && $expected{0} === '/') {
				return preg_match($expected, $val);
			} elseif ($op === '=' &&  $val != $expected) {
				return false;
			} elseif ($op === '!=' && $val == $expected) {
				return false;
			} elseif ($op === '>' && $val <= $expected) {
				return false;
			} elseif ($op === '<' && $val >= $expected) {
				return false;
			} elseif ($op === '<=' && $val > $expected) {
				return false;
			} elseif ($op === '>=' && $val < $expected) {
				return false;
			}
		}
		return true;
	}

	/**
	 * This method can be thought of as a hybrid between PHP's `array_merge()`
	 * and `array_merge_recursive()`.  The difference to the two is that if an
	 * array key contains another array then the function behaves recursive
	 * (unlike `array_merge()`) but does not do if for keys containing strings
	 * (unlike `array_merge_recursive()`).  Please note: This function will work
	 * with an unlimited amount of arguments and typecasts non-array parameters
	 * into arrays.
	 *
	 * @return array Merged array of all passed params.
	 */
	public static function merge($arr1, $arr2 = null) {
		$args = array($arr1, $arr2);

		if (empty($arr1) && empty($arr2)) {
			return array();
		}
		if (empty($arr1) || empty($arr2)) {
			return empty($arr1) ? (array) $arr2 : (array) $arr1;
		}
		$result = (array) current($args);

		while (($arg = next($args)) !== false) {
			foreach ((array) $arg as $key => $val) {
				if (is_array($val) && isset($result[$key]) && is_array($result[$key])) {
					$result[$key] = static::merge($result[$key], $val);
				} elseif (is_int($key)) {
					$result[] = $val;
				} else {
					$result[$key] = $val;
				}
			}
		}
		return $result;
	}

	/**
	 * Normalizes a string or array list.
	 *
	 * @param mixed $list List to normalize.
	 * @param boolean $assoc If `true`, `$list` will be converted to an associative array.
	 * @param string $sep If `$list` is a string, it will be split into an array with `$sep`.
	 * @param boolean $trim If `true`, separated strings will be trimmed.
	 * @return array
	 */
	public static function normalize($list, $assoc = true, $sep = ',', $trim = true) {
		if (is_string($list)) {
			$list = explode($sep, $list);
			if ($trim) {
				foreach ($list as $key => $value) {
					$list[$key] = trim($value);
				}
			}
			if ($assoc) {
				return static::normalize($list);
			}
		} elseif (is_array($list)) {
			$keys = array_keys($list);
			$count = count($keys);
			$numeric = true;

			if (!$assoc) {
				for ($i = 0; $i < $count; $i++) {
					if (!is_int($keys[$i])) {
						$numeric = false;
						break;
					}
				}
			}

			if (!$numeric || $assoc) {
				$newList = array();
				for ($i = 0; $i < $count; $i++) {
					if (is_int($keys[$i]) && is_scalar($list[$keys[$i]])) {
						$newList[$list[$keys[$i]]] = null;
					} else {
						$newList[$keys[$i]] = $list[$keys[$i]];
					}
				}
				$list = $newList;
			}
		}
		return $list;
	}

	/**
	 * Removes an element from an array as defined by `$path`.
	 *
	 * @param mixed $list From where to remove.
	 * @param mixed $path A dot-delimited string.
	 * @return array Array with `$path` removed from its value.
	 */
	public static function remove($list, $path = null) {
		if (empty($path)) {
			return $list;
		}
		if (!is_array($path)) {
			$path = explode('.', $path);
		}
		$_list =& $list;

		foreach ($path as $i => $key) {
			if (is_numeric($key) && intval($key) > 0 || $key === '0') {
				$key = intval($key);
			}
			if ($i === count($path) - 1) {
				unset($_list[$key]);
			} else {
				if (!isset($_list[$key])) {
					return $list;
				}
				$_list =& $_list[$key];
			}
		}
		return $list;
	}

	/**
	 * Sorts an array by any value, determined by a `Set`-compatible path.
	 *
	 * @param array $data
	 * @param string $path A `Set`-compatible path to the array value.
	 * @param string $dir Either `'asc'` (the default) or `'desc'`.
	 * @return array
	 */
	public static function sort($data, $path, $dir = 'asc') {
		$flatten = function($flatten, $results, $key = null) {
			$stack = array();
			foreach ((array) $results as $k => $r) {
				$id = $k;
				if (!is_null($key)) {
					$id = $key;
				}
				if (is_array($r)) {
					$stack = array_merge($stack, $flatten($flatten, $r, $id));
				} else {
					$stack[] = array('id' => $id, 'value' => $r);
				}
			}
			return $stack;
		};
		$extract = static::extract($data, $path);
		$result = $flatten($flatten, $extract);

		list($keys, $values) = array(
			static::extract($result, '/id'),
			static::extract($result, '/value')
		);
		$dir = ($dir === 'desc') ? SORT_DESC : SORT_ASC;
		array_multisort($values, $dir, $keys, $dir);
		$sorted = array();
		$keys = array_unique($keys);

		foreach ($keys as $k) {
			$sorted[] = $data[$k];
		}
		return $sorted;
	}

	/**
	 * Genric method for converting arrays and objects between different types
	 *
	 * @param string $type The type to convert to : array|object
	 * @param string $data The array or object data to convert
	 * @param string $options
	 * @return void
	 */
	public static function to($type, $data, array $options = array()) {
		if ($type === 'object') {
			return static::_toObject($data, $options);
		}
		return static::_toArray($data);
	}

	/**
	 * Converts an object into an array. If `$object` is no object, reverse
	 * will return the same value.
	 *
	 * @param object $object Object to make into an array.
	 * @return array
	 */
	public static function _toArray($object) {
		$out = array();
		if (is_object($object)) {
			$keys = get_object_vars($object);
			if (isset($keys['_name_'])) {
				$identity = $keys['_name_'];
				unset($keys['_name_']);
			}
			$new = array();
			foreach ($keys as $key => $value) {
				if (is_array($value)) {
					$new[$key] = static::_toArray($value);
				} else {
					if (isset($value->_name_)) {
						$new = array_merge($new, static::_toArray($value));
					} else {
						$new[$key] = static::_toArray($value);
					}
				}
			}
			if (isset($identity)) {
				$out[$identity] = $new;
			} else {
				$out = $new;
			}
		} elseif (is_array($object)) {
			foreach ($object as $key => $value) {
				$out[$key] = static::_toArray($value);
			}
		} else {
			$out = $object;
		}
		return $out;
	}

	/**
	 * Maps the contents of the Set object to an object hierarchy.  Maintains numeric
	 * keys as arrays of objects.
	 *
	 * @param array $data The array.
	 * @param string $options
	 * @return object Hierarchical object.
	 */
	public static function _toObject($data, array $options = array()) {
		if (empty($data)) {
			return $data;
		}

		$defaults = array('class' => '\stdClass', 'flatten' => true, 'name' => false);
		$options += $defaults;
		$out = new $options['class'];
		$name = $options['name'];

		if (is_array($data)) {
			$keys = array_keys($data);
			foreach ($data as $key => $value) {
				if (is_numeric($key)) {
					if (is_object($out)) {
						$out = get_object_vars($out);
					}
					$out[$key] = static::_toObject($value, $options);
					$isNamed = (
						!empty($options['name']) && $options['flatten'] === false &&
						is_object($out[$key]) && !isset($out[$key]->_name_) &&
						static::depth($value, true) >= 2
					);
					if ($isNamed) {
						$out[$key]->_name_ = $options['name'];
					}
				} elseif (is_array($value)) {
					if ($options['flatten'] === true) {
						$options['flatten'] = false;
						$out->_name_ = $key;
						foreach ($value as $key2 => $value2) {
							$out->{$key2} = static::_toObject($value2, array('flatten' => false));
						}
					} else {
						if (!is_numeric($key)) {
							$out->{$key} = static::_toObject($value, array(
								'flatten' => false, 'name' => $key
							));
							if (is_object($out->{$key}) && !isset($out->{$key}->_name_)) {
								$out->{$key}->_name_ = $key;
							}
						} else {
							$out->{$key} = static::_toObject($value, array('flatten' => true));
						}
					}
				} else {
					$out->{$key} = $value;
				}
			}
		} else {
			$out = $data;
		}
		return $out;
	}
}

?>