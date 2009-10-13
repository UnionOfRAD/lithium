<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n;

use \lithium\core\Libraries;
use \lithium\util\Collection;

class Catalog extends \lithium\core\StaticObject {

	protected static $_configurations = null;

	public static function __init() {
		static::$_configurations = new Collection();
	}

	public static function config($config = null) {
		$default = array('adapter' => null, 'scope' => null);

		if ($config) {
			$items = array_map(function($i) use ($default) { return $i + $default; }, $config);
			static::$_configurations = new Collection(compact('items'));
		}
		return static::$_configurations;
	}

	/**
	 * Reads data.  Data can be obtained for one or multiple configurations
	 * and locales. The results for list-like categories are aggregated by
	 * querying all requested configurations for the requested locale and then
	 * repeating this process for all locales down the locale cascade. This allows
	 * for sparse data which is complemented by data from other sources or
	 * for more generic locales. Aggregation can be controlled by either specifying
	 * the configurations or a scope to use.
	 *
	 * Usage:
	 * {{{
	 * Catalog::read('message.page', array('zh', 'en'));
	 * Catalog::read('validation.postalCode', 'en_US');
	 * }}}
	 *
	 * @param string $category For a list of all valid categories
	 *              please {@see catalog\adapters\Base::$_categories}.
	 * @param string|array $locales One or multiple locales.
	 * @param array $options Valid options are:
	 *              - `'name'` One or multiple configuration names.
	 *              - `'scope'` The scope to use.
	 * @return array|void If available the requested data, else `null`.
	 */
	public static function read($category, $locales, $options = array()) {
		$defaults = array('name' => null, 'scope' => null);
		$options += $defaults;

		$names = (array)$options['name'] ?: static::$_configurations->keys();
		$results = null;

		foreach ((array)$locales as $locale) {
			foreach (Locale::cascade($locale) as $cascaded) {
				foreach ($names as $name) {
					$adapter = static::_adapter($name);

					if (!$adapter->isSupported($category, __FUNCTION__)) {
						continue;
					}
					if (!$result = $adapter->read($category, $cascaded, $options['scope'])) {
						continue;
					}
					if (!is_array($result)) {
						$results[$locale] = $result;
						break 2;
					}
					if (!isset($results[$locale])) {
						$results[$locale] = array();
					}
					$results[$locale] += $result;
				}
			}
		}
		return $results;
	}

	/**
	 * Writes data.
	 *
	 * Usage:
	 * {{{
	 * $data = array(
	 *   'pl' => array(
	 *      'color' => 'Kolor'
	 *   )
	 *   'ja' => array(
	 *      'color' => '色'
	 * ));
	 * Catalog::write('message.page', $data, array('name' => 'runtime'));
	 * }}}
	 *
	 * @param string $category For a list of all valid categories
	 *              please {@see catalog\adapters\Base::$_categories}.
	 * @param array Data keyed by locale.
	 * @param array $options Valid options are:
	 *              - `'name'` One or multiple configuration names.
	 *              - `'scope'` The scope to use.
	 * @return boolean Success.
	 */
	public static function write($category, $data, $options = array()) {
		$defaults = array('name' => null, 'scope' => null);
		$options += $defaults;

		$names = (array)$options['name'] ?: static::$_configurations->keys();

		foreach ($names as $name) {
			$adapter = static::_adapter($name);

			if (!$adapter->isSupported($category, __FUNCTION__)) {
				continue;
			}
			foreach ($data as $locale => $item) {
				if (!$adapter->write($category, $locale, $options['scope'], $item)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	public static function clear() {
		static::__init();
	}

	public static function _adapter($name = null) {
		if (empty($name)) {
			$names = static::$_configurations->keys();
			if (empty($names)) {
				return;
			}
			$name = end($names);
		}
		if (!isset(static::$_configurations[$name])) {
			return;
		}
		if (is_string(static::$_configurations[$name]['adapter'])) {
			$config = static::$_configurations[$name];
			$class = Libraries::locate('adapters.g11n.catalog', $config['adapter']);
			$conf = array('adapter' => new $class($config)) + static::$_configurations[$name];
			static::$_configurations[$name] = $conf;
		}
		return static::$_configurations[$name]['adapter'];
	}
}

?>