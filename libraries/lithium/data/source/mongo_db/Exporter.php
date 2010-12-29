<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source\mongo_db;

use lithium\util\Set;

class Exporter extends \lithium\core\StaticObject {

	public static function get($type, $export, array $options = array()) {
		$defaults = array('whitelist' => array());
		$options += $defaults;

		if (!method_exists(get_called_class(), $method = "_{$type}") || !$export) {
			return;
		}
		return static::$method($export, array('finalize' => true) + $options);
	}

	public static function toCommand($changes) {
		$map = array(
			'create'    => null,
			'update'    => '$set',
			'increment' => '$inc',
			'remove'    => '$remove',
			'rename'    => '$rename',
		);
		$result = array();

		foreach ($map as $from => $to) {
			if (!isset($changes[$from])) {
				continue;
			}
			if (!$to) {
				$result = array_merge($result, $changes[$from]);
			}
			$result[$to] = $changes[$from];
		}
		unset($result['$set']['_id']);
		return $result;
	}

	protected static function _create($export, array $options) {
		$export += array('data' => array(), 'update' => array(), 'remove' => array(), 'key' => '');
		$data = array_merge($export['data'], $export['update']);
		$data = array_diff_key($data, $export['remove']);

		$result = array('create' => array());
		$localOpts = array('finalize' => false) + $options;

		foreach ($data as $key => $val) {
			if (is_object($val) && method_exists($val, 'export')) {
				$data[$key] = static::_create($val->export($options), $localOpts);
			}
		}
		return ($options['finalize']) ? array('create' => $data) : $data;
	}

	protected static function _update($export) {
		$export += array('update' => array(), 'remove' => array(), 'key' => '');
		$path = $export['key'] ? "{$export['key']}." : "";
		$data = $export['update'];
		$result = array();

		if (!$export['exists']) {
			$data = array_merge($export['data'], $data);
		}
		$data = array_diff_key($data, $export['remove']);
		$nested = array_diff_key($export['data'], $data);

		foreach ($export['remove'] as $key => $val) {
			$result['remove']["{$path}{$key}"] = $val;
		}

		foreach ($data as $key => $val) {
			if (is_object($val) && method_exists($val, 'export')) {
				$result = static::_appendObject($result, $path, $key, $val);
				continue;
			}
			$result['update']["{$path}{$key}"] = $val;
		}

		foreach (array_diff_key($nested, $export['remove']) as $key => $val) {
			if (is_object($val) && method_exists($val, 'export')) {
				$result = static::_appendObject($result, $path, $key, $val);
			}
		}
		return $result;
	}

	protected static function _appendObject($changes, $path, $key, $object) {
		$options =  array('finalize' => false);

		if ($object->exists()) {
			return Set::merge($changes, static::_update($object->export()));
		}
		$changes['update']["{$path}{$key}"] = static::_create($object->export(), $options);
		return $changes;
	}
}

?>