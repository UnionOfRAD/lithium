<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\g11n;

/**
 * Globalization data is not just translated messages, it is validation rules, formats and a lot
 * more. Generally speaking is the `Catalog` class allowing us to retrieve and store globalized
 * data, providing low-level functionality to other classes.
 *
 * The class is able to aggregate data from different sources which allows to complement sparse
 * data. Not all categories must be supported by an individual adapter.
 */
class Catalog extends \lithium\core\Adaptable {

	/**
	 * A Collection of the configurations you add through Catalog::config().
	 *
	 * @var Collection
	 */
	protected static $_configurations = [];

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.g11n.catalog';

	/**
	 * Sets configurations for this Adaptable implementation.
	 *
	 * @param array $config Configurations, indexed by name.
	 * @return array `Collection` of configurations or void if setting configurations.
	 */
	public static function config($config = null) {
		$defaults = ['scope' => null];

		if (is_array($config)) {
			foreach ($config as $i => $item) {
				$config[$i] += $defaults;
			}
		}
		return parent::config($config);
	}

	/**
	 * Reads data.
	 *
	 * Results are aggregated by querying all requested configurations for the requested
	 * locale then repeating this process for all locales down the locale cascade. This
	 * allows for sparse data which is complemented by data from other sources or for more
	 * generic locales. Aggregation can be controlled by either specifying the configurations
	 * or a scope to use.
	 *
	 * Usage:
	 * ```
	 * Catalog::read(true, 'message', 'zh');
	 * Catalog::read('default', 'message', 'zh');
	 * Catalog::read('default', 'validation.postalCode', 'en_US');
	 * ```
	 *
	 * @param mixed $name Provide a single configuration name as a string or multiple ones as
	 *        an array which will be used to read from. Pass `true` to use all configurations.
	 * @param string $category A (dot-delimeted) category.
	 * @param string $locale A locale identifier.
	 * @param array $options Valid options are:
	 *        - `'scope'`: The scope to use.
	 *        - `'lossy'`: Whether or not to use the compact and lossy format, defaults to `true`.
	 * @return array If available the requested data, else `null`.
	 */
	public static function read($name, $category, $locale, array $options = []) {
		$defaults = ['scope' => null, 'lossy' => true];
		$options += $defaults;

		$category = strtok($category, '.');
		$id = strtok('.');

		$names = $name === true ? array_keys(static::$_configurations) : (array) $name;
		$results = [];

		foreach (Locale::cascade($locale) as $cascaded) {
			foreach ($names as $name) {
				$adapter = static::adapter($name);

				if ($result = $adapter->read($category, $cascaded, $options['scope'])) {
					$results += $result;
				}
			}
		}
		if ($options['lossy']) {
			array_walk($results, function(&$value) {
				$value = $value['translated'];
			});
		}

		if ($id) {
			return isset($results[$id]) ? $results[$id] : null;
		}
		return $results ?: null;
	}

	/**
	 * Writes data.
	 *
	 * Usage:
	 * ```
	 * $data = [
	 * 	'color' => '色'
	 * ];
	 * Catalog::write('runtime', 'message', 'ja', $data);
	 * ```
	 *
	 * @param string $name Provide a configuration name to use for writing.
	 * @param string $category A (dot-delimited) category.
	 * @param string $locale A locale identifier.
	 * @param mixed $data If method is used without specifying an id must be an array.
	 * @param array $options Valid options are:
	 *        - `'scope'`: The scope to use.
	 * @return boolean Success.
	 */
	public static function write($name, $category, $locale, $data, array $options = []) {
		$defaults = ['scope' => null];
		$options += $defaults;

		$category = strtok($category, '.');
		$id = strtok('.');

		if ($id) {
			$data = [$id => $data];
		}

		array_walk($data, function(&$value, $key) {
			if (!is_array($value) || !array_key_exists('translated', $value)) {
				$value = ['id' => $key, 'translated' => $value];
			}
		});

		$adapter = static::adapter($name);
		return $adapter->write($category, $locale, $options['scope'], $data);
	}
}

?>