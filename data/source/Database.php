<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use lithium\util\String;
use lithium\util\Inflector;
use InvalidArgumentException;

/**
 * The `Database` class provides the base-level abstraction for SQL-oriented relational databases.
 * It handles all aspects of abstraction, including formatting for basic query types and SQL
 * fragments (i.e. for joins), converting `Query` objects to SQL, and various other functionality
 * which is shared across multiple relational databases.
 *
 * @see lithium\data\model\Query
 */
abstract class Database extends \lithium\data\Source {

	/**
	 * The supported column types and their default values
	 *
	 * @var array
	 */
	protected $_columns = array(
		'string' => array('length' => 255)
	);

	/**
	 * Strings used to render the given statement
	 *
	 * @see lithium\data\source\Database::renderCommand()
	 * @var array
	 */
	protected $_strings = array(
		'create' => "INSERT INTO {:source} ({:fields}) VALUES ({:values});{:comment}",
		'update' => "UPDATE {:source} SET {:fields} {:conditions};{:comment}",
		'delete' => "DELETE {:flags} FROM {:source} {:conditions};{:comment}",
		'schema' => "CREATE TABLE {:source} (\n{:columns}{:indexes});{:comment}",
		'join'   => "{:type} JOIN {:source} {:alias} {:constraint}"
	);

	/**
	 * Classes used by `Database`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'entity' => 'lithium\data\entity\Record',
		'set' => 'lithium\data\collection\RecordSet',
		'relationship' => 'lithium\data\model\Relationship'
	);

	/**
	 * List of SQL operators, paired with handling options.
	 *
	 * @var array
	 */
	protected $_operators = array(
		'='  => array('multiple' => 'IN'),
		'<'  => array(),
		'>'  => array(),
		'<=' => array(),
		'>=' => array(),
		'!=' => array('multiple' => 'NOT IN'),
		'<>' => array('multiple' => 'NOT IN'),
		'between' => array('format' => 'BETWEEN ? AND ?'),
		'BETWEEN' => array('format' => 'BETWEEN ? AND ?'),
		'like' => array(),
		'LIKE' => array(),
		'not like' => array(),
		'NOT LIKE' => array()
	);

	protected $_constraintTypes = array(
		'AND' => true,
		'and' => true,
		'OR' => true,
		'or' => true
	);

	/**
	 * A pair of opening/closing quote characters used for quoting identifiers in SQL queries.
	 *
	 * @var array
	 */
	protected $_quotes = array();

	/**
	 * Getter/Setter for the connection's encoding
	 * Abstract. Must be defined by child class.
	 *
	 * @param mixed $encoding
	 * @return mixed.
	 */
	abstract public function encoding($encoding = null);

	/**
	 * Return the last errors produced by a the execution of a query.
 	 * Abstract. Must be defined by child class.
 	 *
	 */
	abstract public function error();

	/**
	 * Execute a given query
 	 * Abstract. Must be defined by child class.
 	 *
 	 * @see lithium\data\source\Database::renderCommand()
	 * @param string $sql The sql string to execute
	 * @return resource
	 */
	abstract protected function _execute($sql);

	/**
	 * Get the last insert id from the database.
	 * Abstract. Must be defined by child class.
	 *
	 * @param $query lithium\data\model\Query $context The given query.
	 * @return void
	 */
	abstract protected function _insertId($query);

	/**
	 * Creates the database object and set default values for it.
	 *
	 * Options defined:
	 *  - 'database' _string_ Name of the database to use. Defaults to `null`.
	 *  - 'host' _string_ Name/address of server to connect to. Defaults to 'localhost'.
	 *  - 'login' _string_ Username to use when connecting to server. Defaults to 'root'.
	 *  - 'password' _string_ Password to use when connecting to server. Defaults to `''`.
	 *  - 'persistent' _boolean_ If true a persistent connection will be attempted, provided the
	 *    adapter supports it. Defaults to `true`.
	 *
	 * @param $config array Array of configuration options.
	 * @return Database object.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'persistent' => true,
			'host'       => 'localhost',
			'login'      => 'root',
			'password'   => '',
			'database'   => null
		);
		$this->_strings += array(
			'read' => 'SELECT {:fields} FROM {:source} {:alias} {:joins} {:conditions} {:group} ' .
			          '{:order} {:limit};{:comment}'
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Field name handler to ensure proper escaping.
	 *
	 * @param string $name
	 * @return string
	 */
	public function name($name) {
		$open  = reset($this->_quotes);
		$close = next($this->_quotes);
		if (preg_match('/^[a-z0-9_-]+\.[a-z0-9_-]+$/i', $name)) {
			list($first, $second) = explode('.', $name, 2);
			return "{$open}{$first}{$close}.{$open}{$second}{$close}";
		}
		return preg_match('/^[a-z0-9_-]+$/i', $name) ? "{$open}{$name}{$close}" : $name;
	}

	/**
	 * Converts a given value into the proper type based on a given schema definition.
	 *
	 * @see lithium\data\source\Database::schema()
	 * @param mixed $value The value to be converted. Arrays will be recursively converted.
	 * @param array $schema Formatted array from `lithium\data\source\Database::schema()`
	 * @return mixed value with converted type
	 */
	public function value($value, array $schema = array()) {
		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$value[$key] = $this->value($val, isset($schema[$key]) ? $schema[$key] : $schema);
			}
			return $value;
		}
		if ($value === null) {
			return 'NULL';
		}
		switch ($type = isset($schema['type']) ? $schema['type'] : $this->_introspectType($value)) {
			case 'boolean':
				return $this->_toNativeBoolean($value);
			case 'float':
				return floatval($value);
			case 'integer':
				return intval($value);
		}
	}

	/**
	 * Inserts a new record into the database based on a the `Query`. The record is updated
	 * with the id of the insert.
	 *
	 * @see lithium\util\String::insert()
	 * @param object $query An SQL query string, or `lithium\data\model\Query` object instance.
	 * @param array $options If $query is a string, $options contains an array of bind values to be
	 *              escaped, quoted, and inserted into `$query` using `String::insert()`.
	 * @return boolean Returns `true` if the query succeeded, otherwise `false`.
	 * @filter
	 */
	public function create($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			$query = $params['query'];
			$model = $entity = $object = $id = null;

			if (is_object($query)) {
				$object = $query;
				$model = $query->model();
				$params = $query->export($self);
				$entity =& $query->entity();
				$query = $self->renderCommand('create', $params, $query);
			} else {
				$query = String::insert($query, $self->value($params['options']));
			}

			if (!$self->invokeMethod('_execute', array($query))) {
				return false;
			}

			if ($entity) {
				if (($model) && !$model::key($entity)) {
					$id = $self->invokeMethod('_insertId', array($object));
				}
				$entity->sync($id);
			}
			return true;
		});
	}

	/**
	 * Reads records from a database using a `lithium\data\model\Query` object or raw SQL string.
	 *
	 * @param string|object $query `lithium\data\model\Query` object or SQL string.
	 * @param array $options If `$query` is a raw string, contains the values that will be escaped
	 *               and quoted. Other options:
	 *               - `'return'` _string_: switch return between `'array'`, `'item'`, or
	 *                 `'resource'` _string_: Defaults to `'item'`.
	 * @return mixed Determined by `$options['return']`.
	 * @filter
	 */
	public function read($query, array $options = array()) {
		$defaults = array(
			'return' => is_string($query) ? 'array' : 'item', 'schema' => array()
		);
		$options += $defaults;

		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			$query = $params['query'];
			$args = $params['options'];
			$return = $args['return'];
			unset($args['return']);

			$model = is_object($query) ? $query->model() : null;

			if (is_string($query)) {
				$sql = String::insert($query, $self->value($args));
			} else {
				$limit = $query->limit();
				if ($model && $limit && !isset($args['subquery']) && $model::relations('hasMany')) {
					$name = $model::meta('name');
					$key = $model::key();

					$subQuery = $self->invokeMethod('_instance', array(
							get_class($query), array(
								'type' => 'read',
								'model' => $model,
								'group' => "{$name}.{$key}",
								'fields' => array("{$name}.{$key}"),
								'joins' => $query->joins(),
								'conditions' => $query->conditions(),
								'limit' => $query->limit(),
								'page' => $query->page(),
								'order' => $query->order()
							)
						));
					$ids = $self->read($subQuery, array('subquery' => true));
					$idData = $ids->data();
					$ids = array_map(function($index) use ($key) {
							return $index[$key];
						}, $idData);
					$query->limit(false)->conditions(array("{$name}.{$key}" => $ids));
				}
				$sql = $self->renderCommand($query);
			}
			$result = $self->invokeMethod('_execute', array($sql));

			switch ($return) {
				case 'resource':
					return $result;
				case 'array':
					$columns = $args['schema'] ?: $self->schema($query, $result);
					$records = array();
					if (is_array(reset($columns))) {
						$columns = reset($columns);
					}
					while ($data = $result->next()) {
						// @hack: Fix this to support relationships
						if (count($columns) != count($data) && is_array(current($columns))) {
							$columns = current($columns);
						}
						$records[] = array_combine($columns, $data);
					}
					return $records;
				case 'item':
					return $self->item($query->model(), array(), compact('query', 'result') + array(
						'class' => 'set'
					));
			}
		});
	}

	/**
	 * Updates a record in the database based on the given `Query`.
	 *
	 * @param object $query A `lithium\data\model\Query` object
	 * @param array $options none
	 * @return boolean
	 * @filter
	 */
	public function update($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			$query = $params['query'];
			$params = $query->export($self);
			$sql = $self->renderCommand('update', $params, $query);

			if ($self->invokeMethod('_execute', array($sql))) {
				if ($query->entity()) {
					$query->entity()->sync();
				}
				return true;
			}
			return false;
		});
	}

	/**
	 * Deletes a record in the database based on the given `Query`.
	 *
	 * @param object $query An SQL string, or `lithium\data\model\Query` object instance.
	 * @param array $options If `$query` is a string, `$options` is the array of quoted/escaped
	 *              parameter values to be inserted into the query.
	 * @return boolean Returns `true` on successful query execution (not necessarily if records are
	 *         deleted), otherwise `false`.
	 * @filter
	 */
	public function delete($query, array $options = array()) {
		return $this->_filter(__METHOD__, compact('query', 'options'), function($self, $params) {
			$query = $params['query'];

			if (is_object($query)) {
				$data = $query->export($self);
				$sql = $self->renderCommand('delete', $data, $query);
			} else {
				$sql = String::insert($query, $self->value($params['options']));
			}
			return (boolean) $self->invokeMethod('_execute', array($sql));
		});
	}

	/**
	 * Executes calculation-related queries, such as those required for `count` and other
	 * aggregates.
	 *
	 * @param string $type Only accepts `count`.
	 * @param mixed $query The query to be executed.
	 * @param array $options Optional arguments for the `read()` query that will be executed
	 *        to obtain the calculation result.
	 * @return integer Result of the calculation.
	 */
	public function calculation($type, $query, array $options = array()) {
		$query->calculate($type);

		switch ($type) {
			case 'count':
				if (strpos($fields = $this->fields($query->fields(), $query), ',') !== false) {
					$fields = "*";
				}
				$query->fields("COUNT({$fields}) as count", true);
				$query->map(array($query->alias() => array('count')));
				list($record) = $this->read($query, $options)->data();
				return isset($record['count']) ? intval($record['count']) : null;
		}
	}

	/**
	 * Defines or modifies the default settings of a relationship between two models.
	 *
	 * @param string $class the primary model of the relationship
	 * @param string $type the type of the relationship (hasMany, hasOne, belongsTo)
	 * @param string $name the name of the relationship
	 * @param array $config relationship options
	 * @return array Returns an array containing the configuration for a model relationship.
	 */
	public function relationship($class, $type, $name, array $config = array()) {
		$field = Inflector::underscore(Inflector::singularize($name));//($type == 'hasMany') ?  : ;
		$key = "{$field}_id";
		$primary = $class::meta('key');

		if (is_array($primary)) {
			$key = array_combine($primary, $primary);
		} elseif ($type == 'hasMany' || $type == 'hasOne') {
			if ($type == 'hasMany') {
				$field = Inflector::pluralize($field);
			}
			$secondary = Inflector::underscore(Inflector::singularize($class::meta('name')));
			$key = array($primary => "{$secondary}_id");
		}

		$from = $class;
		$fieldName = $field;
		$config += compact('type', 'name', 'key', 'from', 'fieldName');
		return $this->_instance('relationship', $config);
	}

	/**
	 * Returns a given `type` statement for the given data, rendered from `Database::$_strings`.
	 *
	 * @param string $type One of `'create'`, `'read'`, `'update'`, `'delete'` or `'join'`.
	 * @param string $data The data to replace in the string.
	 * @param string $context
	 * @return string
	 */
	public function renderCommand($type, $data = null, $context = null) {
		if (is_object($type)) {
			$context = $type;
			$data = $context->export($this);
			$type = $context->type();
		}
		if (!isset($this->_strings[$type])) {
			throw new InvalidArgumentException("Invalid query type `{$type}`.");
		}
		$data = array_filter($data);
		return trim(String::insert($this->_strings[$type], $data, array('clean' => true)));
	}

	/**
	 * Builds an array of keyed on the fully-namespaced `Model` with array of fields as values
	 * for the given `Query`
	 *
	 * @param object $query A `lithium\data\model\Query` object
	 * @param string $resource
	 * @param string $context
	 * @return void
	 */
	public function schema($query, $resource = null, $context = null) {
		$model = is_scalar($resource) ? $resource : $query->model();
		$modelName = (method_exists($context, 'alias') ? $context->alias() : $query->alias());
		$fields = $query->fields();
		$joins = (array) $query->joins();
		$result = array();

		if (!$model && is_array($fields)) {
			return array($fields);
		}

		if (!$fields && !$joins) {
			return array($modelName => array_keys($model::schema()));
		}

		if (!$fields && $joins) {
			$return = array($modelName => array_keys($model::schema()));
			foreach ($joins as $join) {
				$model = $join->model();
				$return[$join->alias()] = array_keys($model::schema());
			}
			return $return;
		}

		$relations = array_keys((array) $query->relationships());
		$schema = $model::schema();
		$pregDotMatch = '/^(' . implode('|', array_merge($relations, array($modelName))) . ')\./';
		$forJoin = ($modelName != $query->alias());

		foreach ($fields as $scope => $field) {
			switch (true) {
				case (is_numeric($scope) && ($field == '*' || $field == $modelName)):
					$result[$modelName] = array_keys($model::schema());
				break;
				case (is_numeric($scope) && isset($schema[$field])):
					$result[$modelName][] = $field;
				break;
				case is_numeric($scope) && preg_match($pregDotMatch, $field):
					list($dotModelName, $field) = explode('.', $field);
					$result[$dotModelName][] = $field;
					break;
				case is_array($field) && $scope == $modelName:
					$result[$modelName] = $field;
				break;
				case $forJoin || !$joins;
					continue;
				case in_array($scope, $relations) && is_array($field):
					$join = isset($joins[$scope]) ? $joins[$scope] : null;
					if ($join) {
						$relSchema = $this->schema($query, $join->model(), $join);
						$result[$scope] = reset($relSchema);
					}
				break;
				case is_numeric($scope) && in_array($field, $relations):
					$join = isset($joins[$field]) ? $joins[$field] : null;
					if (!$join) {
						continue;
					}
					$scope = $join->model();
					$result[$field] = array_keys($scope::schema());
				break;
			}
		}
		if (!$forJoin) {
			$sortOrder = array_flip(array_merge(array($modelName), $relations));
			uksort($result, function($a, $b) use ($sortOrder) {
				return $sortOrder[$a] - $sortOrder[$b];
			});
		}
		return $result;
	}

	/**
	 * Returns a string of formatted conditions to be inserted into the query statement. If the
	 * query conditions are defined as an array, key pairs are converted to SQL strings.
	 *
	 * Conversion rules are as follows:
	 *
	 * - If `$key` is numeric and `$value` is a string, `$value` is treated as a literal SQL
	 *   fragment and returned.
	 *
	 * @param string|array $conditions The conditions for this query.
	 * @param object $context The current `lithium\data\model\Query` instance.
	 * @param array $options
	 *               - `prepend` _boolean_: Whether the return string should be prepended with the
	 *                 `WHERE` keyword.
	 * @return string Returns the `WHERE` clause of an SQL query.
	 */
	public function conditions($conditions, $context, array $options = array()) {
		$defaults = array('prepend' => true);
		$ops = $this->_operators;
		$options += $defaults;
		$model = $context->model();
		$schema = $model ? $model::schema() : array();

		switch (true) {
			case empty($conditions):
				return '';
			case is_string($conditions):
				return ($options['prepend']) ? "WHERE {$conditions}" : $conditions;
			case !is_array($conditions):
				return '';
		}
		$result = array();

		foreach ($conditions as $key => $value) {
			$schema[$key] = isset($schema[$key]) ? $schema[$key] : array();
			$return = $this->_processConditions($key,$value, $schema);

			if ($return) {
				$result[] = $return;
			}
		}
		$result = join(" AND ", $result);
		return ($options['prepend'] && $result) ? "WHERE {$result}" : $result;
	}

	public function _processConditions($key, $value, $schema, $glue = 'AND') {
		$constraintTypes =& $this->_constraintTypes;

		switch (true) {
			case (is_numeric($key) && is_string($value)):
				return $value;
			case is_string($value):
				return $this->name($key) . ' = ' . $this->value($value, $schema[$key]);
			case is_numeric($key) && is_array($value):
				$result = array();
				foreach ($value as $cField => $cValue) {
					$result[] = $this->_processConditions($cField, $cValue, $schema, $glue);
				}
				return '(' . implode(' ' . $glue . ' ', $result) . ')';
			case (is_string($key) && is_object($value)):
				$value = trim(rtrim($this->renderCommand($value), ';'));
				return "{$key} IN ({$value})";
			case is_array($value) && isset($constraintTypes[strtoupper($key)]):
				$result = array();
				$glue = strtoupper($key);

				foreach ($value as $cField => $cValue) {
					$result[] = $this->_processConditions($cField, $cValue, $schema, $glue);
				}
				return '(' . implode(' ' . $glue . ' ', $result) . ')';
			case (is_string($key) && is_array($value) && isset($this->_operators[key($value)])):
				foreach ($value as $op => $val) {
					$result[] = $this->_operator($key, array($op => $val), $schema[$key]);
				}
				return '(' . implode(' ' . $glue . ' ', $result) . ')';
			case is_array($value):
				$value = join(', ', $this->value($value, $schema[$key]));
				return "{$key} IN ({$value})";
			default:
				if (isset($value)) {
					$value = $this->value($value, $schema[$key]);
					return "{$key} = {$value}";
				}
				if ($value === null) {
					return "{$key} IS NULL";
				}
		}
	}

	/**
	 * Returns either a formatted string for a select query, or an array of key/value pairs for a
	 * create or update query.
	 *
	 * @param array $fields Either an array of field names for a select, or key/value pairs for
	 *              a create or update query.
	 * @param string $context An instance of `Query`, containing the details of the query to be run.
	 * @return mixed Returns a string or array, depending on the query type to be performed (as
	 *         determined by `$context->type()`).
	 */
	public function fields($fields, $context) {
		$type = $context->type();
		$schema = (array) $context->schema();
		$modelNames = (array) $context->name();
		$modelNames = array_merge($modelNames, array_keys((array) $context->relationships()));

		if (!is_array($fields)) {
			return $this->_fieldsReturn($type, $context, $fields, $schema);
		}
		$toMerge = array();
		$keys = array_keys($fields);

		$groupFields = function($item, $key) use (&$toMerge, &$keys, $modelNames, &$context) {
			$name = current($keys);
			next($keys);
			switch (true) {
				case is_array($item):
					$toMerge[$name] = $item;
					continue;
				case in_array($item, $modelNames):
					if ($item == reset($modelNames)) {
						$schema = $context->schema();
					} else {
						$joins = $context->joins();
						$schema = $joins[$item]->schema();
					}
					$toMerge[$item] = array_keys($schema);
					continue;
				case strpos($item, '.') !== false:
					list($name, $field) = explode('.', $item);
					$toMerge[$name][] = $field;
					continue;
				default:
					$mainSchema = array_keys((array)$context->schema());
					if (in_array($item, $mainSchema)) {
						$toMerge[reset($modelNames)][] = $item;
						continue;
					}
					$toMerge[0][] = $item;
					continue;
			}
		};
		array_walk($fields, $groupFields);
		$fields = $toMerge;

		if (count($modelNames) > 1) {
			$sortOrder = array_flip($modelNames);
			uksort($fields, function($a, $b) use ($sortOrder) {
				return $sortOrder[$a] - $sortOrder[$b];
			});
		}
		$mapFields = function() use($fields, $modelNames) {
			$return = array();
			foreach ($fields as $key => $items) {
				if (!is_array($items)) {
					$return[$key] = $items;
					continue;
				}
				if (is_numeric($key)) {
					$key = reset($modelNames);
				}
				$pointer = &$return[$key];
				foreach ($items as $field) {
					if (stripos($field, ' as ') !== false) {
						list($real, $as) = explode(' as ', str_replace(' AS ', ' as ', $field));
						$pointer[] = trim($as);
						continue;
					}
					$pointer[] = $field;
				}
			}
			return $return;
		};
		$context->map($mapFields());

		$toMerge = array();
		foreach ($fields as $scope => $items) {
			foreach ($items as $field) {
				if (!is_numeric($scope)) {
					$toMerge[] = $scope . '.' . $field;
					continue;
				}
				$toMerge[] = $field;
			}
		}
		$fields = $toMerge;
		return $this->_fieldsReturn($type, $context, $fields, $schema);
	}

	protected function _fieldsReturn($type, $context, $fields, $schema) {
		if ($type == 'create' || $type == 'update') {
			$data = $context->data();

			if ($fields && is_array($fields) && is_int(key($fields))) {
				$data = array_intersect_key($data, array_combine($fields, $fields));
			}
			$method = "_{$type}Fields";
			return $this->{$method}($data, $schema, $context);
		}
		return empty($fields) ? '*' : join(', ', $fields);
	}

	/**
	 * Returns a LIMIT statement from the given limit and the offset of the context object.
	 *
	 * @param integer $limit An
	 * @param object $context The `lithium\data\model\Query` object
	 * @return string
	 */
	public function limit($limit, $context) {
		if (!$limit) {
			return;
		}
		if ($offset = $context->offset() ?: '') {
			$offset .= ', ';
		}
		return "LIMIT {$offset}{$limit}";
	}

	/**
	 * Returns a join statement for given array of query objects
	 *
	 * @param object|array $joins A single or array of `lithium\data\model\Query` objects
	 * @param object $context The parent `lithium\data\model\Query` object
	 * @return string
	 */
	public function joins(array $joins, $context) {
		$result = null;

		foreach ($joins as $model => $join) {
			if ($result) {
				$result .= ' ';
			}
			$result .= $this->renderCommand('join', $join->export($this));
		}
		return $result;
	}

	public function constraint($constraint, $context) {
		if (!$constraint) {
			return "";
		}
		if (is_string($constraint)) {
			return "ON {$constraint}";
		}
		$result = array();

		foreach ($constraint as $field => $value) {
			$field = $this->name($field);

			if (is_string($value)) {
				$result[] = $field . ' = ' . $this->name($value);
				continue;
			}
			if (!is_array($value)) {
				continue;
			}
			foreach ($value as $operator => $val) {
				if (isset($this->_operators[$operator])) {
					$val = $this->name($val);
					$result[] = "{$field} {$operator} {$val}";
				}
			}
		}
		return 'ON ' . join(' AND ', $result);
	}

	/**
	 * Return formatted clause for order.
	 *
	 * @param mixed $order The `order` clause to be formatted
	 * @param object $context
	 * @return mixed Formatted `order` clause.
	 */
	public function order($order, $context) {
		$direction = 'ASC';
		$model = $context->model();

		if (is_string($order)) {
			if (!$model::schema($order)) {
				$match = '/\s+(A|DE)SC/i';
				return "ORDER BY {$order}" . (preg_match($match, $order) ? '' : " {$direction}");
			}
			$order = array($order => $direction);
		}

		if (!is_array($order)) {
			return;
		}
		$result = array();

		foreach ($order as $column => $dir) {
			if (is_int($column)) {
				$column = $dir;
				$dir = $direction;
			}
			$dir = in_array($dir, array('ASC', 'asc', 'DESC', 'desc')) ? $dir : $direction;

			if (!$model) {
				$result[] = "{$column} {$dir}";
				continue;
			}
			if ($field = $model::schema($column)) {
				$name = $this->name($model::meta('name')) . '.' . $this->name($column);
				$result[] = "{$name} {$dir}";
			}
		}
		$order = join(', ', $result);
		return "ORDER BY {$order}";
	}

	public function group($group, $context = null) {
		if (!$group) {
			return null;
		}
		return 'GROUP BY ' . join(', ', (array) $group);
	}

	/**
	 * Adds formatting to SQL comments before they're embedded in queries.
	 *
	 * @param string $comment
	 * @return string
	 */
	public function comment($comment) {
		return $comment ? "/* {$comment} */" : null;
	}

	public function alias($alias, $context) {
		if (!$alias && ($model = $context->model())) {
			$alias = $model::meta('name');
		}
		return $alias ? "AS " . $this->name($alias) : null;
	}

	public function cast($entity, array $data, array $options = array()) {
		return $data;
	}

	protected function _createFields($data, $schema, $context) {
		$fields = $values = array();

		while (list($field, $value) = each($data)) {
			$fields[] = $this->name($field);
			$values[] = $this->value($value, isset($schema[$field]) ? $schema[$field] : array());
		}
		$fields = join(', ', $fields);
		$values = join(', ', $values);
		return compact('fields', 'values');
	}

	protected function _updateFields($data, $schema, $context) {
		$fields = array();

		while (list($field, $value) = each($data)) {
			$schema += array($field => array('default' => null));
			$fields[] = $this->name($field) . ' = ' . $this->value($value, $schema[$field]);
		}
		return join(', ', $fields);
	}

	/**
	 * Handles conversion of SQL operator keys to SQL statements.
	 *
	 * @param string $key Key in a conditions array. Usually a field name.
	 * @param mixed $value An SQL operator or comparison value.
	 * @param array $schema An array defining the schema of the field used in the criteria.
	 * @param array $options
	 * @return string Returns an SQL string representing part of a `WHERE` clause of a query.
	 */
	protected function _operator($key, $value, array $schema = array(), array $options = array()) {
		$defaults = array('boolean' => 'AND');
		$options += $defaults;

		list($op, $value) = each($value);
		$config = $this->_operators[$op];
		$key = $this->name($key);
		$values = array();

		if (!is_object($value)) {
			foreach ((array) $value as $val) {
				$values[] = $this->value($val, $schema);
			}
		}

		switch (true) {
			case (isset($config['format'])):
				return $key . ' ' . String::insert($config['format'], $values);
			case (is_object($value) && isset($config['multiple'])):
				$op = $config['multiple'];
				$value = trim(rtrim($this->renderCommand($value), ';'));
				return "{$key} {$op} ({$value})";
			case (count($values) > 1 && isset($config['multiple'])):
				$op = $config['multiple'];
				$values = join(', ', $values);
				return "{$key} {$op} ({$values})";
			case (count($values) > 1):
				return join(" {$options['boolean']} ", array_map(
					function($v) use ($key, $op) { return "{$key} {$op} {$v}"; }, $values
				));
		}
		return "{$key} {$op} {$values[0]}";
	}

	/**
	 * Returns a fully-qualified table name (i.e. with prefix), quoted.
	 *
	 * @param string $entity
	 * @return string
	 */
	protected function _entityName($entity) {
		return $this->name($entity);
	}

	/**
	 * Attempts to automatically determine the column type of a value. Used by the `value()` method
	 * of various database adapters to determine how to prepare a value if the schema is not
	 * specified.
	 *
	 * @param mixed $value The value to be prepared for an SQL query.
	 * @return string Returns the name of the column type which `$value` most likely belongs to.
	 */
	protected function _introspectType($value) {
		switch (true) {
			case (is_bool($value)):
				return 'boolean';
			case (is_float($value) || preg_match('/^\d+\.\d+$/', $value)):
				return 'float';
			case (is_int($value) || preg_match('/^\d+$/', $value)):
				return 'integer';
			case (is_string($value) && strlen($value) <= $this->_columns['string']['length']):
				return 'string';
			default:
				return 'text';
		}
	}

	/**
	 * Casts a value which is being written or compared to a boolean-type database column.
	 *
	 * @param mixed $value A value of unknown type to be cast to boolean. Numeric values not equal
	 *              to zero evaluate to `true`, otherwise `false`. String values equal to `'true'`,
	 *              `'t'` or `'T'` evaluate to `true`, all others to `false`. In all other cases,
	 *               uses PHP's default casting.
	 * @return boolean Returns a boolean representation of `$value`, based on the comparison rules
	 *         specified above. Database adapters may override this method if boolean type coercion
	 *         is required and falls outside the rules defined.
	 */
	protected function _toBoolean($value) {
		if (is_bool($value)) {
			return $value;
		}
		if (is_int($value) || is_float($value)) {
			return ($value !== 0);
		}
		if (is_string($value)) {
			return ($value == 't' || $value == 'T' || $value == 'true');
		}
		return (boolean) $value;
	}

	protected function _toNativeBoolean($value) {
		return $value ? 1 : 0;
	}
}

?>