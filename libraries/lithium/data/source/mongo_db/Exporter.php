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

	protected static $_map = array(
		'MongoId'      => 'id',
		'MongoDate'    => 'date',
		'MongoCode'    => 'code',
		'MongoBinData' => 'binary',
		'datetime'     => 'date',
		'timestamp'    => 'date',
		'int'          => 'integer',
	);

	public static function get($type, $export, array $options = array()) {
		$defaults = array('whitelist' => array());
		$options += $defaults;

		if (!method_exists(get_called_class(), $method = "_{$type}") || !$export) {
			return;
		}
		return static::$method($export, array('finalize' => true) + $options);
	}

	public static function cast($data, $schema, $database, array $options = array()) {
		$defaults = array(
			'pathKey' => null, 'handlers' => array(), 'model' => null, 'arrays' => true
		);
		$options += $defaults;
		$typeMap = static::$_map;

		foreach ($data as $key => $value) {
			if (is_object($value)) {
				continue;
			}
			$path = $options['pathKey'] ? "{$options['pathKey']}.{$key}" : $key;
			$field = (isset($schema[$path]) ? $schema[$path] : array());
			$field += array('type' => null, 'array' => null);
			$type = isset($typeMap[$field['type']]) ? $typeMap[$field['type']] : $field['type'];

			$isObject = ($type == 'object');
			$isArray = (is_array($value) && $field['array'] !== false && !$isObject);
			$isArray = $field['array'] || $isArray;

			if (isset($options['handlers'][$type]) && $handler = $options['handlers'][$type]) {
				$value = $isArray ? array_map($handler, (array) $value) : $handler($value);
			}
			if (!$options['arrays']) {
				$data[$key] = $value;
				continue;
			}
			$pathKey = $path;

			if (!is_array($value) && !$field['array']) {
				$data[$key] = $value;
				continue;
			}

			if ($field['array']) {
				$opts = array('class' => 'array') + $options;
				$value = ($value === null) ? array() : $value;
				$value = is_array($value) ? $value : array($value);
			} elseif (is_array($value)) {
				$arrayType = !$isObject && (array_keys($value) === range(0, count($value) - 1));
				$opts = $arrayType ? array('class' => 'array') + $options : $options;
			}
			$data[$key] = $database->item($options['model'], $value, compact('pathKey') + $opts);
		}
		return $data;
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