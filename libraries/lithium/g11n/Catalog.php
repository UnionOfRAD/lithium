<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n;

use \lithium\core\Libraries;
use \lithium\util\Collection;

/**
 * Globalization data is not just translated messages, it is validation rules, formats and a lot
 * more, too. Data is grouped into 4 different kinds of categories: inflection, validation, message
 * and list.
 *
 * Generally speaking is the `Catalog` class allowing us to retrieve and store globalized
 * data, providing low-level functionality to other classes. Its interface is similar to classes
 * like Session or Cache and like those extensible through adapters.
 *
 * We need to deal with different kinds of sources for this data, but we don't want differing
 * results depending on the adapter in use. This is why results are kept in a neutral inter-
 * changeable format. You can rely on getting the same format of obtained results independent
 * from the adapter they're coming from.
 *
 * The class is able to aggregate data from different sources which allows to complement sparse
 * data. Not all categories must be supported by an individual adapter.
 */
class Catalog extends \lithium\core\Adaptable {

	protected static $_configurations = null;

	public static function config($config = null) {
		$default = array('scope' => null);

		if ($config) {
			$config = array_map(function($i) use ($default) { return $i + $default; }, $config);
		}
		return parent::config($config);
	}

	/**
	 * Reads data.  Data can be obtained for one or multiple configurations and locales.
	 *
	 * The results for list-like categories are aggregated by querying all requested
	 * configurations for the requested locale and then repeating this process for all locales down
	 * the locale cascade. This allows for sparse data which is complemented by data from other
	 * sources or for more generic locales. Aggregation can be controlled by either specifying the
	 * configurations or a scope to use.
	 *
	 * Usage:
	 * {{{
	 * Catalog::read('message.page', array('zh', 'en'));
	 * Catalog::read('validation.postalCode', 'en_US');
	 * }}}
	 *
	 * @param string $category Dot-delimeted category.
	 * @param string|array $locales One or multiple locales.
	 * @param array $options Valid options are:
	 *        - `'name'`: One or multiple configuration names.
	 *        - `'scope'`: The scope to use.
	 * @return array|void If available the requested data, else `null`.
	 * @see lithium\g11n\catalog\adapter\Base::$_categories.
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
	 * @param string $category Dot-delimeted category.
	 * @param array Data keyed by locale.
	 * @param array $options Valid options are:
	 *        - `'name'`: One or multiple configuration names.
	 *        - `'scope'`: The scope to use.
	 * @return boolean Success.
	 * @see lithium\g11n\catalog\adapter\Base::$_categories.
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

	public static function adapter($name) {
		return static::_adapter('adapter.g11n.catalog', $name);
	}
}

?>