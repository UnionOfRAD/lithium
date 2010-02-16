<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n;

use \lithium\core\Libraries;
use \lithium\util\Collection;

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
	protected static $_configurations = null;

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.g11n.catalog';

	public static function config($config = null) {
		$default = array('scope' => null);

		if ($config) {
			$config = array_map(function($i) use ($default) { return $i + $default; }, $config);
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
	 * {{{
	 * Catalog::read('message', 'zh');
	 * Catalog::read('validation.postalCode', 'en_US');
	 * }}}
	 *
	 * @param string $category A (dot-delimeted) category.
	 * @param string $locale A locale identifier.
	 * @param array $options Valid options are:
	 *        - `'name'`: One or multiple configuration names.
	 *        - `'scope'`: The scope to use.
	 *        - `'lossy'`: Whether or not to use the compact and lossy format, defaults to `true`.
	 * @return array|void If available the requested data, else `null`.
	 */
	public static function read($category, $locale, $options = array()) {
		$defaults = array('name' => null, 'scope' => null, 'lossy' => true);
		$options += $defaults;

		$category = strtok($category, '.');
		$id = strtok('.');

		$names = (array) $options['name'] ?: static::$_configurations->keys();
		$results = array();

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
	 * {{{
	 * $data = array(
	 * 	'color' => '色'
	 * );
	 * Catalog::write('message', 'ja', $data, array('name' => 'runtime'));
	 * }}}
	 *
	 * @param string $category A (dot-delimited) category.
	 * @param string $locale A locale identifier.
	 * @param array $data
	 * @param array $options Valid options are:
	 *        - `'name'`: One or multiple configuration names.
	 *        - `'scope'`: The scope to use.
	 * @return boolean Success.
	 */
	public static function write($category, $locale, $data, $options = array()) {
		$defaults = array('name' => null, 'scope' => null);
		$options += $defaults;

		$category = strtok($category, '.');
		$id = strtok('.');

		if ($id) {
			$data = array($id => $data);
		}

		array_walk($data, function(&$value, $key) {
			if (!is_array($value) || !array_key_exists('translated', $value)) {
				$value = array('id' => $key, 'translated' => $value);
			}
		});

		$names = (array) $options['name'] ?: static::$_configurations->keys();

		foreach ($names as $name) {
			$adapter = static::adapter($name);
			return $adapter->write($category, $locale, $options['scope'], $data);
		}
		return false;
	}
}

?>