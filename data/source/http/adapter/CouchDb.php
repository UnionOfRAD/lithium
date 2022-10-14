<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\data\source\http\adapter;

use lithium\aop\Filters;
use lithium\core\ConfigException;
use lithium\core\Libraries;

/**
 * A data source adapter which allows you to connect to Apache CouchDB.
 *
 * By default, it will attempt to connect to the CouchDB running on `localhost` on port
 * 5984 using HTTP version 1.0.
 *
 * @link http://couchdb.apache.org
 */
class CouchDb extends \lithium\data\source\Http {

	/**
	 * Increment value of current result set loop
	 * used by `result` to handle rows of json responses.
	 *
	 * @var string
	 */
	protected $_iterator = 0;

	/**
	 * True if Database exists.
	 *
	 * @var boolean
	 */
	protected $_db = false;

	/**
	 * Classes used by `CouchDb`.
	 *
	 * @var array
	 */
	protected $_classes = [
		'service' => 'lithium\net\http\Service',
		'entity'  => 'lithium\data\entity\Document',
		'set'     => 'lithium\data\collection\DocumentSet',
		'schema'  => 'lithium\data\DocumentSchema'
	];

	protected $_handlers = [];

	/**
	 * Constructor.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'port'` _integer_
	 *        - `'version'` _integer_
	 *        - `'database'` _string_
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = ['port' => 5984, 'version' => 1, 'database' => null];
		parent::__construct($config + $defaults);
	}

	protected function _init() {
		parent::_init();
		$this->_handlers += [
			'integer' => function($v) { return (integer) $v; },
			'float'   => function($v) { return (float) $v; },
			'boolean' => function($v) { return (boolean) $v; }
		];
	}

	/**
	 * Destructor. Ensures that the server connection is closed and resources are freed when
	 * the adapter instance is destroyed.
	 *
	 * @return void
	 */
	public function __destruct() {
		if (!$this->_isConnected) {
			return;
		}
		$this->disconnect();
		$this->_db = false;
		unset($this->connection);
	}

	/**
	 * Configures a model class by setting the primary key to `'id'`, in keeping with CouchDb
	 * conventions.
	 *
	 * @see lithium\data\Model::$_meta
	 * @see lithium\data\Model::$_classes
	 * @param string $class The fully-namespaced model class name to be configured.
	 * @return Returns an array containing keys `'classes'` and `'meta'`, which will be merged with
	 *         their respective properties in `Model`.
	 */
	public function configureClass($class) {
		return [
			'classes' => $this->_classes,
			'meta' => ['key' => 'id', 'locked' => false],
			'schema' => [
				'id' => ['type' => 'string'],
				'rev' => ['type' => 'string']
			]
		];
	}

	/**
	 * Magic for passing methods to http service.
	 *
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 */
	public function __call($method, $params = []) {
		list($path, $data, $options) = ($params + ['/', [], []]);
		return json_decode($this->connection->{$method}($path, $data, $options) ?? '');
	}

	/**
	 * Returns an array of object types accessible through this database.
	 *
	 * @param object $class
	 * @return void
	 */
	public function sources($class = null) {}

	/**
	 * Describe database, create if it does not exist.
	 *
	 * @throws ConfigException
	 * @param string $entity
	 * @param array $schema Any schema data pre-defined by the model.
	 * @param array $meta
	 * @return lithium\data\Schema
	 */
	public function describe($entity, $schema = [], array $meta = []) {
		$database = $this->_config['database'];

		if (!$this->_db) {
			$result = $this->get($database);

			if (isset($result->db_name)) {
				$this->_db = true;
			}
			if (!$this->_db) {
				if (isset($result->error)) {
					if ($result->error === 'not_found') {
						$result = $this->put($database);
					}
				}
				if (isset($result->ok) || isset($result->db_name)) {
					$this->_db = true;
				}
			}
		}
		if (!$this->_db) {
			throw new ConfigException("Database `{$entity}` is not available.");
		}
		return Libraries::instance(null, 'schema', [['fields' => $schema]], $this->_classes);
	}

	/**
	 * Quotes identifiers.
	 *
	 * CouchDb does not need identifiers quoted, so this method simply returns the identifier.
	 *
	 * @param string $name The identifier to quote.
	 * @return string The quoted identifier.
	 */
	public function name($name) {
		return $name;
	}

	/**
	 * Create new document.
	 *
	 * @param object $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function create($query, array $options = []) {
		$defaults = ['model' => $query->model()];
		$options += $defaults;
		$params = compact('query', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$request = ['type' => 'json'];
			$query = $params['query'];
			$options = $params['options'];
			$data = $query->data();
			$data += ['type' => $options['model']::meta('source')];

			if (isset($data['id'])) {
				return $this->update($query, $options);
			}

			$retry = false;
			do {
				$result = $this->connection->post($this->_config['database'], $data, $request);
				$result = is_string($result) ? json_decode($result, true) : $result;

				$retry = $retry ? !$retry : $this->_autoBuild($result);
			} while ($retry);

			if (isset($result['_id']) || (isset($result['ok']) && $result['ok'] === true)) {
				$result = $this->_format($result, $options);
				$query->entity()->sync($result['id'], $result);
				return true;
			}
			return false;
		});
	}

	/**
	 * Read from document.
	 *
	 * @param object $query
	 * @param array $options
	 * @return object
	 * @filter
	 */
	public function read($query, array $options = []) {
		$defaults = ['return' => 'resource', 'model' => $query->model()];
		$options += $defaults;
		$params = compact('query', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$query = $params['query'];
			$params = $query->export($this);

			list($_path, $conditions) = (array) $params['conditions'];
			$model = $query->model();

			if (empty($_path)) {
				$_path = '_all_docs';
				$conditions['include_docs'] = 'true';
			}
			$path = "{$this->_config['database']}/{$_path}";
			$args = (array) $conditions + (array) $params['limit'] + (array) $params['order'];

			$result = $this->connection->get($path, $args);
			$result = is_string($result) ? json_decode($result, true) : $result;

			$data = $stats = [];

			if (isset($result['_id'])) {
				$data = [$result];
			} elseif (isset($result['rows'])) {
				$data = $result['rows'];
				unset($result['rows']);
				$stats = $result;
			}
			$stats += ['total_rows' => null, 'offset' => null];
			$opts = compact('stats') + [
				'class' => 'set', 'exists' => true, 'defaults' => false
			];

			return $this->item($model, $data, $opts);
		});
	}

	/**
	 * Update document.
	 *
	 * @param string $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function update($query, array $options = []) {
		$params = compact('query', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$query = $params['query'];
			$options = $params['options'];
			$params = $query->export($this);

			list($_path, $conditions) = (array) $params['conditions'];
			$data = $query->data();

			foreach (['id', 'rev'] as $key) {
				$data["_{$key}"] = isset($data[$key]) ? (string) $data[$key] : null;
				unset($data[$key]);
			}
			$data = (array) $conditions + array_filter((array) $data);

			$retry = false;
			do {
				$result = $this->connection->put(
					"{$this->_config['database']}/{$_path}",
					$data,
					['type' => 'json']
				);
				$result = is_string($result) ? json_decode($result, true) : $result;
				$retry = $retry ? !$retry : $this->_autoBuild($result);
			} while ($retry);

			if (isset($result['_id']) || (isset($result['ok']) && $result['ok'] === true)) {
				$result = $this->_format($result, $options);
				$query->entity()->sync($result['id'], ['rev' => $result['rev']]);
				return true;
			}
			if (isset($result['error'])) {
				$query->entity()->errors([$result['error']]);
			}
			return false;
		});
	}

	/**
	 * Helper used for auto building a CouchDB database.
	 *
	 * @param string $result A query result.
	 */
	protected function _autoBuild($result) {
		$hasError = (
			isset($result['error']) &&
			isset($result['reason']) &&
			$result['reason'] === "no_db_file"
		);
		if ($hasError) {
			$this->connection->put($this->_config['database']);
			return true;
		}
		return false;
	}

	/**
	 * Delete document.
	 *
	 * @param string $query
	 * @param array $options
	 * @return boolean
	 * @filter
	 */
	public function delete($query, array $options = []) {
		$params = compact('query', 'options');

		return Filters::run($this, __FUNCTION__, $params, function($params) {
			$query = $params['query'];
			$params = $query->export($this);
			list($_path, $conditions) = $params['conditions'];
			$data = $query->data();

			if (!empty($data['rev'])) {
				$conditions['rev'] = $data['rev'];
			}
			$result = $this->connection->delete(
				"{$this->_config['database']}/{$_path}",
				$conditions
			);
			$result = json_decode($result);
			$result = (isset($result->ok) && $result->ok === true);

			if ($query->entity()) {
				$query->entity()->sync(null, [], ['dematerialize' => true]);
			}
			return $result;
		});
	}

	/**
	 * Executes calculation-related queries, such as those required for `count`.
	 *
	 * @param string $type Only accepts `count`.
	 * @param mixed $query The query to be executed.
	 * @param array $options Optional arguments for the `read()` query that will be executed
	 *        to obtain the calculation result.
	 * @return integer Result of the calculation.
	 */
	public function calculation($type, $query, array $options = []) {
		switch ($type) {
			case 'count':
				return (integer) $this->read($query, $options)->stats('total_rows');
			default:
				return null;
		}
	}

	/**
	 * Returns a newly-created `Document` object, bound to a model and populated with default data
	 * and options.
	 *
	 * @param string $model A fully-namespaced class name representing the model class to which the
	 *               `Document` object will be bound.
	 * @param array $data The default data with which the new `Document` should be populated.
	 * @param array $options Any additional options to pass to the `Document`'s constructor
	 * @return object Returns a new, un-saved `Document` object bound to the model class specified
	 *         in `$model`.
	 */
	public function item($model, array $data = [], array $options = []) {
		$defaults = ['class' => 'entity'];
		$options += $defaults;

		if ($options['class'] === 'entity') {
			return $model::create($this->_format($data), $options);
		}

		foreach ($data as $key => $value) {
			if (isset($value['doc'])) {
				$value = $value['doc'];
			}
			if (isset($value['value'])) {
				$value = $value['value'];
			}
			$data[$key] = $this->_format($value);
		}
		return $model::create($data, $options);
	}

	/**
	 * Handle conditions.
	 *
	 * @param string $conditions
	 * @param string $context
	 * @return array
	 */
	public function conditions($conditions, $context) {
		$path = null;
		if (isset($conditions['design'])) {
			$paths = ['design', 'view'];
			foreach ($paths as $element) {
				if (isset($conditions[$element])) {
					$path .= "_{$element}/{$conditions[$element]}/";
					unset($conditions[$element]);
				}
			}
		}
		if (isset($conditions['id'])) {
			$path = "{$conditions['id']}";
			unset($conditions['id']);
		}
		if (isset($conditions['path'])) {
			$path = "{$conditions['path']}";
			unset($conditions['path']);
		}
		return [$path, $conditions];
	}

	/**
	 * Fields for query.
	 *
	 * @param string $fields
	 * @param string $context
	 * @return array
	 */
	public function fields($fields, $context) {
		return $fields ?: [];
	}

	/**
	 * Limit for query.
	 *
	 * @param string $limit
	 * @param string $context
	 * @return array
	 */
	public function limit($limit, $context) {
		return compact('limit') ?: [];
	}

	/**
	 * Order for query.
	 *
	 * @param string $order
	 * @param string $context
	 * @return array
	 */
	public function order($order, $context) {
		return (array) $order ?: [];
	}

	/**
	 * With no parameter, always returns `true`, since CouchDB only depends on HTTP. With a
	 * parameter, queries for a specific supported feature.
	 *
	 * @param string $feature Test for support for a specific feature, i.e. `"transactions"` or
	 *               `"arrays"`.
	 * @return boolean Returns `true` if the particular feature support is enabled, otherwise
	 *         `false`.
	 */
	public static function enabled($feature = null) {
		if (!$feature) {
			return true;
		}
		$features = [
			'arrays' => true,
			'transactions' => false,
			'booleans' => true,
			'relationships' => false,
			'schema' => false,
			'sources' => false
		];
		return isset($features[$feature]) ? $features[$feature] : null;
	}

	/**
	 * Formats a CouchDb result set into a standard result to be passed to item.
	 *
	 * @param array $data data returned from query
	 * @return array
	 */
	protected function _format(array $data) {
		foreach (['id', 'rev'] as $key) {
			if (isset($data["_{$key}"])) {
				$data[$key] = $data["_{$key}"];
				unset($data["_{$key}"]);
			}
		}
		return $data;
	}

}

?>