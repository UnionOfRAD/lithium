<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\data\source;

use PDO;
use PDOException;
use lithium\util\Set;
use lithium\util\String;
use lithium\util\Inflector;
use lithium\core\ConfigException;
use lithium\core\NetworkException;
use lithium\data\model\QueryException;
use lithium\data\model\Query;
use InvalidArgumentException;
use UnexpectedValueException;

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
	 * @var PDO
	 */
	public $connection;

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
		'join' => "{:mode} JOIN {:source} {:alias} {:constraints}",
		'schema' => "CREATE TABLE {:source} (\n{:columns}{:constraints}){:table};{:comment}",
		'drop'   => "DROP TABLE {:exists}{:source};"
	);

	/**
	 * Classes used by `Database`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'entity' => 'lithium\data\entity\Record',
		'set' => 'lithium\data\collection\RecordSet',
		'relationship' => 'lithium\data\model\Relationship',
		'result' => 'lithium\data\source\database\adapter\pdo\Result',
		'schema' => 'lithium\data\Schema',
		'query' => 'lithium\data\model\Query'
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
		'BETWEEN' => array('format' => 'BETWEEN ? AND ?'),
		'LIKE' => array(),
		'NOT LIKE' => array(),
		'IS' => array(),
		'IS NOT' => array()
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
	 * Array of named callable objects representing different strategies for performing specific
	 * types of queries.
	 *
	 * @var array
	 */
	protected $_strategies = array();

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
			'database'   => null,
			'encoding'   => null,
			'dsn'        => null,
			'options'    => array()
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Initialize `Database::$_strategies` because Closures cannot be created within the class
	 * definition.
	 *
	 * @see lithium\data\source\Database::$_strategies
	 */
	protected function _init() {
		parent::_init();

		$formatters = $this->_formatters();

		foreach ($this->_columns as $type => $column) {
			if (isset($formatters[$type])) {
				$this->_columns[$type]['formatter'] = $formatters[$type];
			}
		}

		$this->_strings += array(
			'read' => 'SELECT {:fields} FROM {:source} {:alias} {:joins} {:conditions} {:group} ' .
			          '{:having} {:order} {:limit};{:comment}'
		);

		$this->_strategies += array(
			'joined' => function($self, $model, $context) {

				$with = $context->with();

				$strategy = function($me, $model, $tree, $path, $from, &$deps) use ($self, $context, $with) {
					foreach ($tree as $name => $childs) {
						if (!$rel = $model::relations($name)) {
							throw new QueryException("Model relationship `{$name}` not found.");
						}

						$constraints = array();
						$alias = $name;
						$relPath = $path ? $path . '.' . $name : $name;
						if (isset($with[$relPath])) {
							list($unallowed, $allowed) = Set::slice($with[$relPath], array(
								'alias',
								'constraints'
							));
							if ($unallowed) {
								$message = "Only `'alias'` and `'constraints'` are allowed in ";
								$message .= "`'with'` using the `'joined'` strategy.";
								throw new QueryException($message);
							}
							extract($with[$relPath]);
						}
						$to = $context->alias($alias, $relPath);

						$deps[$to] = $deps[$from];
						$deps[$to][] = $from;

						if ($context->relationships($relPath) === null) {
							$context->relationships($relPath, array(
								'type' => $rel->type(),
								'model' => $rel->to(),
								'fieldName' => $rel->fieldName(),
								'alias' => $to
							));
							$self->join($context, $rel, $from, $to, $constraints);
						}

						if (!empty($childs)) {
							$me($me, $rel->to(), $childs, $relPath, $to, $deps);
						}
					}
				};

				$tree = Set::expand(array_fill_keys(array_keys($with), false));
				$alias = $context->alias();
				$deps = array($alias => array());
				$strategy($strategy, $model, $tree, '', $alias, $deps);

				$models = $context->models();
				foreach ($context->fields() as $field) {
					if (!is_string($field)) {
						continue;
					}
					list($alias, $field) = $self->invokeMethod('_splitFieldname', array($field));
					$alias = $alias ?: $field;
					if ($alias && isset($models[$alias])) {
						foreach ($deps[$alias] as $depAlias) {
							$depModel = $models[$depAlias];
							$context->fields(array($depAlias => (array) $depModel::meta('key')));
						}
					}
				}
			},
			'nested' => function($self, $model, $context) {
				throw new QueryException("This strategy is not yet implemented.");
			}
		);
	}

	public function connect() {
		$this->_isConnected = false;
		$config = $this->_config;

		if (!$config['database']) {
			throw new ConfigException('No Database configured');
		}
		if (!$config['dsn']) {
			throw new ConfigException('No DSN setup for DB Connection');
		}
		$dsn = $config['dsn'];

		$options = $config['options'] + array(
			PDO::ATTR_PERSISTENT => $config['persistent'],
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
		);

		try {
			$this->connection = new PDO($dsn, $config['login'], $config['password'], $options);
		} catch (PDOException $e) {
			preg_match('/SQLSTATE\[(.+?)\]/', $e->getMessage(), $code);
			$code = $code[1] ?: 0;
			switch (true) {
				case $code === 'HY000' || substr($code, 0, 2) === '08':
					$msg = "Unable to connect to host `{$config['host']}`.";
					throw new NetworkException($msg, null, $e);
				break;
				case in_array($code, array('28000', '42000')):
					$msg = "Host connected, but could not access database `{$config['database']}`.";
					throw new ConfigException($msg, null, $e);
				break;
			}
			throw new ConfigException("An unknown configuration error has occured.", null, $e);
		}
		$this->_isConnected = true;

		if ($this->_config['encoding']) {
			$this->encoding($this->_config['encoding']);
		}
		return $this->_isConnected;
	}

	/**
	 * Disconnects the adapter from the database.
	 *
	 * @return boolean Returns `true` on success, else `false`.
	 */
	public function disconnect() {
		if ($this->_isConnected) {
			unset($this->connection);
			$this->_isConnected = false;
		}
		return true;
	}

	/**
	 * Field name handler to ensure proper escaping.
	 *
	 * @param string $name Field or identifier name.
	 * @return string Returns `$name` quoted according to the rules and quote characters of the
	 *         database adapter subclass.
	 */
	public function name($name) {
		$open  = reset($this->_quotes);
		$close = next($this->_quotes);

		list($first, $second) = $this->_splitFieldname($name);
		if ($first) {
			return "{$open}{$first}{$close}.{$open}{$second}{$close}";
		}
		return preg_match('/^[a-z0-9_-]+$/i', $name) ? "{$open}{$name}{$close}" : $name;
	}

	/**
	 * Return the alias and the field name from an identifier name.
	 *
	 * @param string $field Field name or identifier name.
	 * @return array Returns an array with the alias (or `null` if not applicable) as first value
	 *         and the field name as second value.
	 */
	protected function _splitFieldname($field) {
		if (is_string($field)) {
			if (preg_match('/^[a-z0-9_-]+\.([a-z0-9_-]+|\*)$/i', $field)) {
				return explode('.', $field, 2);
			}
		}
		return array(null, $field);
	}

	/**
	 * Return the field name from a conditions key.
	 *
	 * @param string $field Field or identifier name.
	 * @return string Returns the field name without the table alias, if applicable.
	 * @todo Eventually, this should be refactored and moved to the Query or Schema
	 *       class. Also, by handling field resolution in this way we are not handling
	 *       cases where query conditions use the same field name in multiple tables.
	 *       e.g. Foos.bar and Bars.bar will both return bar.
	 */
	protected function _fieldName($field) {
		if (is_string($field)) {
			if (preg_match('/^[a-z0-9_-]+\.[a-z0-9_-]+$/i', $field)) {
				list($first, $second) = explode('.', $field, 2);
				return $second;
			}
		}
		return $field;
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

		if (is_object($value) && isset($value->scalar)) {
			return $value->scalar;
		}

		if ($value === null) {
			return 'NULL';
		}

		$type = isset($schema['type']) ? $schema['type'] : $this->_introspectType($value);
		$column = isset($this->_columns[$type]) ? $this->_columns[$type] : null;

		return $this->_cast($type, $value, $column, $schema);
	}

	/**
	 * Cast a value according to a column type, used by `Database::value()`
	 *
	 * @see lithium\data\source\Database::value()
	 * @param string $type Name of the column type
	 * @param string $value Value to cast
	 * @param array $column The column definition
	 * @return mixed Casted value
	 */
	protected function _cast($type, $value, $column, $schema = array()) {
		$column += array('formatter' => null, 'format' => null);
		$schema += array('default' => null, 'null' => false);

		if (is_object($value)) {
			return $value;
		}
		if ($formatter = $column['formatter']) {
			$format = $column['format'];
			return $format ? $formatter($format, $value) : $formatter($value);
		}
		return $this->connection->quote($value);
	}

	/**
	 * Provide an associative array of Closures to be used as the "formatter" key inside of the
	 * `Database::$_columns` specification. Each Closure should return the appropriately quoted
	 * or unquoted value and accept one or two parameters:
	 *  - @param mixed $value to be formatted
	 *  - @param mixed $format to apply to $value
	 *
	 * @see lithium\data\source\Database::$_columns
	 * @see lithium\data\source\Database::_init()
	 * @return array of column types to Closure formatter
	 */
	protected function _formatters() {
		$self = $this;

		$datetime = $timestamp = $date = $time = function($format, $value) use ($self) {
			if ($format && (($time = strtotime($value)) !== false)) {
				$value = date($format, $time);
			}
			return $self->connection->quote($value);
		};

		return compact('datetime', 'timestamp', 'date', 'time') + array(
			'boolean' => function($value) {
				return $value ? 1 : 0;
			}
		);
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
			'return' => is_string($query) ? 'array' : 'item',
			'schema' => null,
			'quotes' => $this->_quotes
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
				if (!$data = $self->invokeMethod('_queryExport', array($query))) {
					return false;
				}
				$sql = $self->renderCommand($data['type'], $data);
			}
			$result = $self->invokeMethod('_execute', array($sql));

			switch ($return) {
				case 'resource':
					return $result;
				case 'array':
					$columns = $args['schema'] ?: $self->schema($query, $result);

					if (!is_array(reset($columns))) {
						$columns = array('' => $columns);
					}

					$i = 0;
					$records = array();
					foreach ($result as $data) {
						$offset = 0;
						$records[$i] = array();
						foreach ($columns as $path => $cols) {
							$len = count($cols);
							$values = array_combine($cols, array_slice($data, $offset, $len));
							if ($path) {
								$records[$i][$path] = $values;
							} else {
								$records[$i] += $values;
							}
							$offset += $len;
						}
						$i++;
					}
					return Set::expand($records);
				case 'item':
					return $self->item($query->model(), array(), compact('query', 'result') + array(
						'class' => 'set'
					));
			}
		});
	}

	/**
	 * Helper which export the query export
	 *
	 * @param object $query The query object
	 * @return array The export array
	 */
	protected function &_queryExport($query) {
		$data = $query->export($this);
		if ($query->limit() && ($model = $query->model())) {
			foreach ($query->relationships() as $relation) {
				if ($relation['type'] === 'hasMany') {
					$name = $model::meta('name');
					$key = $model::key();
					$fields = $data['fields'];
					$fieldname = $this->name("{$name}.{$key}");
					$data['fields'] = "DISTINCT({$fieldname}) AS _ID_";
					$sql = $this->renderCommand('read', $data);
					$result = $this->_execute($sql);

					$ids = array();
					while ($row = $result->next()) {
						$ids[] = $row[0];
					}

					if (!$ids) {
						$return = null;
						return $return;
					}
					$data['fields'] = $fields;
					$data['limit'] = '';
					$data['conditions'] = $this->conditions(array(
						"{$name}.{$key}" => $ids
					), $query);
					return $data;
				}
			}
		}
		return $data;
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
			$result = (boolean) $self->invokeMethod('_execute', array($sql));

			if ($result && is_object($query) && $query->entity()) {
				$query->entity()->sync();
			}
			return $result;
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
			$isObject = is_object($query);

			if ($isObject) {
				$sql = $self->renderCommand('delete', $query->export($self), $query);
			} else {
				$sql = String::insert($query, $self->value($params['options']));
			}
			$result = (boolean) $self->invokeMethod('_execute', array($sql));

			if ($result && $isObject && $query->entity()) {
				$query->entity()->sync(null, array(), array('dematerialize' => true));
			}
			return $result;
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
		$primary = $class::meta('key');

		if (is_array($primary)) {
			$key = array_combine($primary, $primary);
		} elseif ($type === 'hasMany' || $type === 'hasOne') {
			$secondary = Inflector::underscore(Inflector::singularize($class::meta('name')));
			$key = array($primary => "{$secondary}_id");
		} else {
			$key = Inflector::underscore(Inflector::singularize($name)) . '_id';
		}

		$from = $class;
		$fieldName = $this->relationFieldName($type, $name);
		$config += compact('type', 'name', 'key', 'from', 'fieldName');
		return $this->_instance('relationship', $config);
	}

	/**
	 * Determines the set of methods to be used when exporting query values.
	 *
	 * @return array
	 */
	public function methods() {
		$result = parent::methods();
		$key = array_search('schema', $result);
		unset($result[$key]);
		return $result;
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
		$template = $this->_strings[$type];
		$data = array_filter($data);
		return trim(String::insert($template, $data, array('clean' => true)));
	}

	/**
	 * Builds an array of keyed on the fully-namespaced `Model` with array of fields as values
	 * for the given `Query`
	 *
	 * @param data\model\Query $query A Query instance.
	 * @param object $resource
	 * @param object $context
	 */
	public function schema($query, $resource = null, $context = null) {
		if (is_object($query)) {
			$query->applyStrategy($this);
			return $this->_schema($query, $this->_fields($query->fields(), $query));
		}

		$result = array();
		$count = $resource->resource()->columnCount();

		for ($i = 0; $i < $count; $i++) {
			$meta = $resource->resource()->getColumnMeta($i);
			$result[] = $meta['name'];
		}
		return $result;
	}

	/**
	 * Helper method for `data\model\Database::shema()`
	 *
	 * @param data\model\Query $query A Query instance.
	 * @param array $fields Array of formatted fields.
	 */
	protected function _schema($query, $fields = null) {
		$model = $query->model();
		$paths = $query->paths($this);
		$models = $query->models($this);
		$alias = $query->alias();
		$result = array();

		if (!$model) {
			foreach ($fields as $field => $value) {
				if (is_array($value)) {
					$result[$field] = array_keys($value);
				} else {
					$result[''][] = $field;
				}
			}
			return $result;
		}
		if (!$fields) {
			foreach ($paths as $alias => $relation) {
				$model = $models[$alias];
				$result[$relation] = $model::schema()->names();
			}
			return $result;
		}

		$unalias = function ($value) {
			if (is_object($value) && isset($value->scalar)) {
				$value = $value->scalar;
			}
			$aliasing = preg_split("/\s+as\s+/i", $value);
			return isset($aliasing[1]) ? $aliasing[1] : $value;
		};

		if (isset($fields[0])) {
			$raw = array_map($unalias, $fields[0]);
			unset($fields[0]);
		}

		$fields = isset($fields[$alias]) ? array($alias => $fields[$alias]) + $fields : $fields;

		foreach ($fields as $field => $value) {
			if (is_array($value)) {
				if (isset($value['*'])) {
					$relModel = $models[$field];
					$result[$paths[$field]] = $relModel::schema()->names();
				} else {
					$result[$paths[$field]] = array_map($unalias, array_keys($value));
				}
			}
		}

		if (isset($raw)) {
			$result[''] = isset($result['']) ? array_merge($raw, $result['']) : $raw;
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
		$defaults = array('prepend' => 'WHERE');
		$options += $defaults;
		return $this->_conditions($conditions, $context, $options);
	}

	/**
	 * Returns a string of formatted havings to be inserted into the query statement. If the
	 * query havings are defined as an array, key pairs are converted to SQL strings.
	 *
	 * Conversion rules are as follows:
	 *
	 * - If `$key` is numeric and `$value` is a string, `$value` is treated as a literal SQL
	 *   fragment and returned.
	 *
	 * @param string|array $conditions The havings for this query.
	 * @param object $context The current `lithium\data\model\Query` instance.
	 * @param array $options
	 *               - `prepend` _boolean_: Whether the return string should be prepended with the
	 *                 `HAVING` keyword.
	 * @return string Returns the `HAVING` clause of an SQL query.
	 */
	public function having($conditions, $context, array $options = array()) {
		$defaults = array('prepend' => 'HAVING');
		$options += $defaults;
		return $this->_conditions($conditions, $context, $options);
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
	 *               - `prepend` mixed: The string to prepend or false for no prepending
	 * @return string Returns an SQL conditions clause.
	 */
	protected function _conditions($conditions, $context, array $options = array()) {
		$defaults = array('prepend' => false);
		$ops = $this->_operators;
		$options += $defaults;

		switch (true) {
			case empty($conditions):
				return '';
			case is_string($conditions):
				return $options['prepend'] ? $options['prepend'] . " {$conditions}" : $conditions;
			case !is_array($conditions):
				return '';
		}
		$result = array();

		foreach ($conditions as $key => $value) {
			$return = $this->_processConditions($key, $value, $context);

			if ($return) {
				$result[] = $return;
			}
		}
		$result = join(" AND ", $result);
		return ($options['prepend'] && $result) ? $options['prepend'] . " {$result}" : $result;
	}

	protected function _processConditions($key, $value, $context, $schema = null, $glue = 'AND') {
		$constraintTypes =& $this->_constraintTypes;
		$model = $context->model();
		$models = $context->models();

		list($first, $second) = $this->_splitFieldname($key);
		if ($first && isset($models[$first]) && $class = $models[$first]) {
			$schema = $class::schema();
		} elseif ($model) {
			$schema = $model::schema();
		}
		$fieldMeta = $schema ? (array) $schema->fields($second) : array();

		switch (true) {
			case (is_numeric($key) && is_string($value)):
				return $value;
			case is_object($value) && isset($value->scalar):
				if (is_numeric($key)) {
					return $this->value($value);
				}
			case is_scalar($value) || is_null($value):
				if ($context && ($context->type() === 'read') && ($alias = $context->alias())) {
					$key = $this->_aliasing($key, $alias);
				}
				if (isset($value)) {
					return $this->name($key) . ' = ' . $this->value($value, $fieldMeta);
				}
				return $this->name($key) . ' IS NULL';
			case is_numeric($key) && is_array($value):
				$result = array();
				foreach ($value as $cKey => $cValue) {
					$result[] = $this->_processConditions($cKey, $cValue, $context, $schema, $glue);
				}
				return '(' . implode(' ' . $glue . ' ', $result) . ')';
			case (is_string($key) && is_object($value)):
				$value = trim(rtrim($this->renderCommand($value), ';'));
				return "{$this->name($key)} IN ({$value})";
			case is_array($value) && isset($constraintTypes[strtoupper($key)]):
				$result = array();
				$glue = strtoupper($key);

				foreach ($value as $cKey => $cValue) {
					$result[] = $this->_processConditions($cKey, $cValue, $context, $schema, $glue);
				}
				return '(' . implode(' ' . $glue . ' ', $result) . ')';
			case $result = $this->_processOperator($key, $value, $fieldMeta, $glue):
				return $result;
			case is_array($value):
				$value = join(', ', $this->value($value, $fieldMeta));
				return "{$this->name($key)} IN ({$value})";
		}
	}

	/**
	 * Helper method used by `_processConditions`.
	 *
	 * @param string The field name string.
	 * @param array The operator to parse.
	 * @param array The schema of the field.
	 * @param string The glue operator (e.g `'AND'` or '`OR`'.
	 * @return mixed Returns the operator expression string or `false` if no operator
	 *         is applicable.
	 * @throws A `QueryException` if the operator is not supported.
	 */
	protected function _processOperator($key, $value, $fieldMeta, $glue) {
		if (!is_string($key) || !is_array($value)) {
			return false;
		}
		$operator = strtoupper(key($value));
		if (!is_numeric($operator)) {
			if (!isset($this->_operators[$operator])) {
				throw new QueryException("Unsupported operator `{$operator}`.");
			}
			foreach ($value as $op => $val) {
				$result[] = $this->_operator($key, array($op => $val), $fieldMeta);
			}
			return '(' . implode(' ' . $glue . ' ', $result) . ')';
		}
		return false;
	}

	/**
	 * Returns a string of formatted fields to be inserted into the query statement.
	 *
	 * @param array $fields Array of fields.
	 * @param object $context Generally a `data\model\Query` instance.
	 * @return string A SQL formatted string
	 */
	public function fields($fields, $context) {
		$type = $context->type();
		$schema = $context->schema()->fields();
		$alias = $context->alias();

		if (!is_array($fields)) {
			return $this->_fieldsReturn($type, $context, $fields, $schema);
		}

		$context->applyStrategy($this);
		$fields = $this->_fields($fields ? : $context->fields(), $context);
		$context->map($this->_schema($context, $fields));
		$toMerge = array();

		if (isset($fields[0])) {
			foreach ($fields[0] as $val) {
				$toMerge[] = (is_object($val) && isset($val->scalar)) ? $val->scalar : $val;
			}
			unset($fields[0]);
		}

		$fields = isset($fields[$alias]) ? array($alias => $fields[$alias]) + $fields : $fields;

		foreach ($fields as $field => $value) {
			if (is_array($value)) {
				if (isset($value['*'])) {
					$toMerge[] = $this->name($field) . '.*';
					continue;
				}
				foreach ($value as $fieldname => $mode) {
					$toMerge[] = $this->_fieldsQuote($field, $fieldname);
				}
			}
		}

		return $this->_fieldsReturn($type, $context, $toMerge, $schema);
	}

	/**
	 * Helper for `Database::fields()` && `Database::schema()`.
	 * Reformat fields to be alias based.
	 *
	 * @param array $fields Array of fields.
	 * @param object $context Generally a `data\model\Query` instance.
	 * @return array Reformatted fields
	 */
	protected function _fields($fields, $context) {
		$alias = $context->alias();
		$models = $context->models($this);
		$list = array();
		foreach ($fields as $key => $field) {
			if (!is_string($field)) {
				if (isset($models[$key])) {
					$field = array_fill_keys($field, true);
					$list[$key] = isset($list[$key]) ? array_merge($list[$key], $field) : $field;
				} else {
					$list[0][] = is_array($field) ? reset($field) : $field;
				}
				continue;
			}
			if (preg_match('/^([a-z0-9_-]+|\*)$/i', $field)) {
				isset($models[$field]) ? $list[$field]['*'] = true : $list[$alias][$field] = true;
			} elseif (preg_match('/^([a-z0-9_-]+)\.(.*)$/i', $field, $matches)) {
				$list[$matches[1]][$matches[2]] = true;
			} else {
				$list[0][] = $field;
			}
		}
		return $list;
	}

	protected function _fieldsQuote($alias, $field) {
		$open = $this->_quotes[0];
		$close = $this->_quotes[1];
		$aliasing = preg_split("/\s+as\s+/i", $field);
		if (isset($aliasing[1])) {
			list($aliasname, $fieldname) = $this->_splitFieldname($aliasing[0]);
			$alias = $aliasname ? : $alias;
			return "{$open}{$alias}{$close}.{$open}{$fieldname}{$close} as {$aliasing[1]}";
		} elseif ($alias) {
			return "{$open}{$alias}{$close}.{$open}{$field}{$close}";
		} else {
			return "{$open}{$field}{$close}";
		}
	}

	protected function _fieldsReturn($type, $context, $fields, $schema) {
		if ($type === 'create' || $type === 'update') {
			$data = $context->data();
			if (isset($data['data']) && is_array($data['data']) && count($data) === 1) {
				$data = $data['data'];
			}

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
			$offset = " OFFSET {$offset}";
		}
		return "LIMIT {$limit}{$offset}";
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

		foreach ($joins as $key => $join) {
			if ($result) {
				$result .= ' ';
			}
			$join = is_array($join) ? $this->_instance('query', $join) : $join;
			$options['keys'] = array('mode', 'source', 'alias', 'constraints');
			$result .= $this->renderCommand('join', $join->export($this, $options));
		}
		return $result;
	}

	/**
	 * Returns a string of formatted constraints to be inserted into the query statement. If the
	 * query constraints are defined as an array, key pairs are converted to SQL strings.
	 *
	 * Conversion rules are as follows:
	 *
	 * - If `$key` is numeric and `$value` is a string, `$value` is treated as a literal SQL
	 *   fragment and returned.
	 *
	 * @param string|array $constraints The constraints for a `ON` clause.
	 * @param object $context The current `lithium\data\model\Query` instance.
	 * @param array $options
	 *               - `prepend` _boolean_: Whether the return string should be prepended with the
	 *                 `ON` keyword.
	 * @return string Returns the `ON` clause of an SQL query.
	 */
	public function constraints($constraints, $context, array $options = array()) {
		$defaults = array('prepend' => 'ON');
		$options += $defaults;
		if (is_array($constraints)) {
			$constraints = $this->_constraints($constraints);
		}
		return $this->_conditions($constraints, $context, $options);
	}

	/**
	 * Auto escape string value to a field name value
	 *
	 * @param array $constraints The constraints array
	 * @return array The escaped constraints array
	 */
	protected function _constraints(array $constraints) {
		foreach ($constraints as &$value) {
			if (is_string($value)) {
				$value = (object) $this->name($value);
			} elseif (is_array($value)) {
				$value = $this->_constraints($value);
			}
		}
		return $constraints;
	}

	/**
	 * Return formatted clause for `ORDER BY`.
	 *
	 * @param mixed $order The clause to be formatted
	 * @param object $context
	 * @return string Formatted clause.
	 */
	public function order($order, $context) {
		return $this->_sort($order, $context);
	}

	/**
	 * Return formatted clause for `GROUP BY`.
	 *
	 * @param mixed $group The clause to be formatted
	 * @param object $context
	 * @return string Formatted clause.
	 */
	public function group($group, $context) {
		return $this->_sort($group, $context, 'GROUP BY', false);
	}

	/**
	 * Helper method
	 *
	 * @see lithium\data\source\Database::order()
	 * @see lithium\data\source\Database::group()
	 * @param mixed $field The field
	 * @param object $context
	 * @return string Formatted clause.
	 */
	protected function _sort($field, $context, $clause = 'ORDER BY', $direction = true) {
		$direction = $direction ? ' ASC' : '';
		$model = $context->model();

		if (is_string($field)) {
			if (preg_match('/^(.*?)\s+((?:A|DE)SC)$/i', $field, $match)) {
				$field = $match[1];
				$direction = $match[2];
			}
			$field = array($field => $direction);
		}

		if (!is_array($field)) {
			return;
		}
		$result = array();

		foreach ($field as $column => $dir) {
			if (is_int($column)) {
				$column = $dir;
				$dir = $direction;
			}
			$dir = in_array($dir, array('ASC', 'asc', 'DESC', 'desc')) ? " {$dir}" : $direction;

			if ($model && $field = $model::schema($column)) {
				$column = $this->name($column);
				$name = $this->name($context->alias()) . '.' . $column;
				$result[] = "{$name}{$dir}";
				continue;
			}
			$column = $this->name($column);
			$result[] = "{$column}{$dir}";
		}
		$fields = join(', ', $result);
		return "$clause {$fields}";
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

		foreach ($data as $field => $value) {
			$fields[] = $this->name($field);
			$values[] = $this->value($value, isset($schema[$field]) ? $schema[$field] : array());
		}
		$fields = join(', ', $fields);
		$values = join(', ', $values);
		return compact('fields', 'values');
	}

	protected function _updateFields($data, $schema, $context) {
		$fields = array();

		foreach ($data as $field => $value) {
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
		$op = strtoupper($op);
		$config = $this->_operators[$op];
		$key = $this->name($key);
		$values = array();

		if (!is_object($value)) {
			if ($value === null) {
				$value = array(null);
			}
			foreach ((array) $value as $val) {
				$values[] = $this->value($val, $schema);
			}
		} elseif (isset($value->scalar)) {
			return "{$key} {$op} {$value->scalar}";
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
	 * @param string $entity A table name or fully-namespaced model class name.
	 * @param array $options Available options:
	 *              - `'quoted'` _boolean_: Indicates whether the name should be quoted.
	 * @return string Returns a quoted table name.
	 */
	protected function _entityName($entity, array $options = array()) {
		$defaults = array('quoted' => false);
		$options += $defaults;

		if (class_exists($entity, false) && method_exists($entity, 'meta')) {
			$entity = $entity::meta('source');
		}
		return $options['quoted'] ? $this->name($entity) : $entity;
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
			return ($value === 't' || $value === 'T' || $value === 'true');
		}
		return (boolean) $value;
	}

	/**
	 * Throw a `QueryException` error
	 *
	 * @param string $sql The offending SQL string
	 * @filter
	 */
	protected function _error($sql){
		$params = compact('sql');
		return $this->_filter(__METHOD__, $params, function($self, $params) {
			$sql = $params['sql'];
			list($code, $error) = $self->error();
			throw new QueryException("{$sql}: {$error}", $code);
		});
	}

	/**
	 * Applying a strategy to a `lithium\data\model\Query` object
	 *
	 * @param array $options The option array
	 * @param object $context A find query object to configure
	 */
	public function applyStrategy($options, $context) {
		if ($context->type() !== 'read') {
			return;
		}

		$options += array('strategy' => 'joined');
		if (!$model = $context->model()) {
			throw new ConfigException('The `\'with\'` option need a valid `\'model\'` option.');
		}

		$strategy = $options['strategy'];
		if (isset($this->_strategies[$strategy])) {
			$strategy = $this->_strategies[$strategy];
			$strategy($this, $model, $context);
		} else {
			throw new QueryException("Undefined query strategy `{$strategy}`.");
		}
	}

	/**
	 * Set a query's join according a Relationship.
	 *
	 * @param object $context A Query instance
	 * @param object $rel A Relationship instance
	 * @param string $fromAlias Set a specific alias for the `'from'` `Model`.
	 * @param string $toAlias Set a specific alias for `'to'` `Model`.
	 * @param mixed $constraints If `$constraints` is an array, it will be merged to defaults
	 *        constraints. If `$constraints` is an object, defaults won't be merged.
	 */
	public function join($context, $rel, $fromAlias = null, $toAlias = null, $constraints = array()) {
		$model = $rel->to();

		if ($fromAlias === null) {
			$from = $rel->from();
			$fromAlias = $context->alias();
		}
		if ($toAlias === null) {
			$toAlias = $context->alias(null, $rel->name());
		}
		if (!is_object($constraints)) {
			$constraints = $this->on($rel, $fromAlias, $toAlias, $constraints);
		} else {
			$constraints = (array) $constraints;
		}

		$context->joins($toAlias, compact('constraints', 'model') + array(
			'mode' => 'LEFT',
			'alias' => $toAlias
		));
	}

	/**
	 * Helper which add an alias basename to a field name if necessary
	 *
	 * @param string $name The field name.
	 * @param string $alias The alias name
	 * @param array $map An array of `'modelname' => 'aliasname'` mapping
	 * @return string
	 */
	protected function _aliasing($name, $alias, $map = array()) {
		list($first, $second) = $this->_splitFieldname($name);
		if (!$first && preg_match('/^[a-z0-9_-]+$/i', $second)) {
			return $alias . "." . $second;
		} elseif (isset($map[$first])) {
			return $map[$first] . "." . $second;
		}
		return $name;
	}

	/**
	 * Build the `ON` constraints from a `Relationship` instance
	 *
	 * @param object $rel A Relationship instance
	 * @param string $fromAlias Set a specific alias for the `'from'` `Model`.
	 * @param string $toAlias Set a specific alias for `'to'` `Model`.
	 * @param array $constraints Array of additionnal $constraints.
	 * @return array A constraints array.
	 */
	public function on($rel, $aliasFrom = null, $aliasTo = null, $constraints = array()) {
		$model = $rel->from();

		$aliasFrom = $aliasFrom ?: $model::meta('name');
		$aliasTo = $aliasTo ?: $rel->name();

		$keyConstraints = array();
		foreach ($rel->key() as $from => $to) {
			$keyConstraints["{$aliasFrom}.{$from}"] = "{$aliasTo}.{$to}";
		}

		$mapAlias = array($model::meta('name') => $aliasFrom, $rel->name() => $aliasTo);

		$relConstraints = $this->_on((array) $rel->constraints(), $aliasFrom, $aliasTo, $mapAlias);
		$constraints = $this->_on($constraints, $aliasFrom, $aliasTo, array());

		return $constraints + $relConstraints + $keyConstraints;
	}

	protected function _on(array $constraints, $aliasFrom, $aliasTo, $mapAlias = array()) {
		$result = array();
		foreach ($constraints as $key => $value) {
			$isAliasable = (
				!is_numeric($key) &&
				!isset($this->_constraintTypes[$key]) &&
				!isset($this->_operators[$key])
			);
			if ($isAliasable) {
				$key = $this->_aliasing($key, $aliasFrom, $mapAlias);
			}
			if (is_string($value)) {
				$result[$key] = $this->_aliasing($value, $aliasTo, $mapAlias);
			} elseif (is_array($value)) {
				$result[$key] = $this->_on($value, $aliasFrom, $aliasTo, $mapAlias);
			} else {
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * Build a SQL column/table meta
	 *
	 * @param string $type The type of the meta to build (possible values: 'table' or 'column')
	 * @param string $name The name of the meta to build
	 * @param mixed $value The value used for building the meta
	 * @return string The SQL meta string
	 */
	protected function _meta($type, $name, $value) {
		$meta = isset($this->_metas[$type][$name]) ? $this->_metas[$type][$name] : null;
		if (!$meta || (isset($meta['options']) && !in_array($value, $meta['options']))) {
			return;
		}
		$meta += array('keyword' => '', 'escape' => false, 'join' => ' ');
		extract($meta);
		if ($escape === true) {
			$value = $this->value($value, array('type' => 'string'));
		}
		$result = $keyword . $join . $value;
		return $result !== ' ' ? $result : '';
	}

	/**
	 * Build a SQL column constraint
	 *
	 * @param string $name The name of the meta to build
	 * @param mixed $value The value used for building the meta
	 * @param object $schema A `Schema` instance.
	 * @return string The SQL meta string
	 */
	protected function _constraint($name, $value, $schema = null) {
		$value += array('options' => array());
		$meta = isset($this->_constraints[$name]) ? $this->_constraints[$name] : null;
		$template = isset($meta['template']) ? $meta['template'] : null;
		if (!$template) {
			return;
		}

		$data = array();
		foreach ($value as $name => $value) {
			switch ($name) {
				case 'key':
				case 'index':
					if (isset($meta[$name])) {
						$data['index'] = $meta[$name];
					}
				break;
				case 'to':
					$data[$name] = $this->name($value);
				break;
				case 'on':
					$data[$name] = "ON {$value}";
				break;
				case 'expr':
					if (is_array($value)) {
						$result = array();
						$context = new Query(array('type' => 'none'));
						foreach ($value as $key => $val) {
							$return = $this->_processConditions($key, $val, $context, $schema);
							if ($return) {
								$result[] = $return;
							}
						}
						$data[$name] = join(" AND ", $result);
					} else {
						$data[$name] = $value;
					}
				break;
				case 'toColumn':
				case 'column':
					$data[$name] = join(', ', array_map(array($this, 'name'), (array) $value));
				break;
			}
		}

		return trim(String::insert($template, $data, array('clean' => array('method' => 'text'))));
	}

	/**
	 * Create a database-native schema
	 *
	 * @param string $name A table name.
	 * @param object $schema A `Schema` instance.
	 * @return boolean `true` on success, `true` otherwise
	 */
	public function createSchema($source, $schema) {

		if (!$schema instanceof $this->_classes['schema']) {
			throw new InvalidArgumentException("Passed schema is not a valid `{$class}` instance.");
		}

		$columns = array();
		$primary = null;

		$source = $this->name($source);

		foreach ($schema->fields() as $name => $field) {
			$field['name'] = $name;
			if ($field['type'] === 'id') {
				$primary = $name;
			}
			$columns[] = $this->column($field);
		}
		$columns = join(",\n", array_filter($columns));

		$metas = $schema->meta() + array('table' => array(), 'constraints' => array());

		$constraints = $this->_buildConstraints($metas['constraints'], $schema, ",\n", $primary);
		$table = $this->_buildMetas('table', $metas['table']);

		$params = compact('source', 'columns', 'constraints', 'table');
		return $this->_execute($this->renderCommand('schema', $params));
	}

	/**
	 * Helper for building columns metas
	 *
	 * @see DatabaseSchema::createSchema()
	 * @see DatabaseSchema::_column()
	 * @param array $metas The array of column metas.
	 * @param array $names If `$names` is not `null` only build meta present in `$names`
	 * @param type $joiner The join character
	 * @return string The SQL constraints
	 */
	protected function _buildMetas($type, array $metas, $names = null, $joiner = ' ') {
		$result = '';
		$names = $names ? (array) $names : array_keys($metas);
		foreach ($names as $name) {
			$value = isset($metas[$name]) ? $metas[$name] : null;
			if ($value && $meta = $this->_meta($type, $name, $value)) {
				$result .= $joiner . $meta;
			}
		}
		return $result;
	}

	/**
	 * Helper for building columns constraints
	 *
	 * @see DatabaseSchema::createSchema()
	 * @param array $constraints The array of constraints
	 * @param type $schema The schema of the table
	 * @param type $joiner The join character
	 * @return string The SQL constraints
	 */
	protected function _buildconstraints(array $constraints, $schema = null, $joiner = ' ', $primary = false) {
		$result = '';
		foreach ($constraints as $constraint) {
			if (isset($constraint['type'])) {
				$name = $constraint['type'];
				if ($meta = $this->_constraint($name, $constraint, $schema)) {
					$result .= $joiner . $meta;
				}
				if ($name === 'primary') {
					$primary = false;
				}
			}
		}
		if ($primary) {
			$result .= $joiner . $this->_constraint('primary', array('column' => $primary));
		}
		return $result;
	}

	/**
	 * Drop a table
	 *
	 * @param string $name The table name to drop.
	 * @param boolean $soft With "soft dropping", the function will retrun `true` even if the
	 *                table doesn't exists.
	 * @return boolean `true` on success, `false` otherwise
	 */
	public function dropSchema($source, $soft = true) {
		if ($source) {
			$source = $this->name($source);
			$exists = $soft ? 'IF EXISTS ' : '';
			return $this->_execute($this->renderCommand('drop', compact('exists', 'source')));
		}
		return false;
	}

	/**
	 * Generate a database-native column schema string
	 *
	 * @param array $column A field array structured like the following:
	 *        `array('name' => 'value', 'type' => 'value' [, options])`, where options can
	 *        be `'default'`, `'null'`, `'length'` or `'precision'`.
	 * @return string SQL string
	 */
	public function column($field) {
		if (!isset($field['type'])) {
			$field['type'] = 'string';
		}

		if (!isset($field['name'])) {
			throw new InvalidArgumentException("Column name not defined.");
		}

		if (!isset($this->_columns[$field['type']])) {
			throw new UnexpectedValueException("Column type `{$field['type']}` does not exist.");
		}

		$field += $this->_columns[$field['type']];

		$field += array(
			'name' => null,
			'type' => null,
			'length' => null,
			'precision' => null,
			'default' => null,
			'null' => null
		);

		$isNumeric = preg_match('/^(integer|float|boolean)$/', $field['type']);
		if ($isNumeric && $field['default'] === '') {
			$field['default'] = null;
		}
		$field['use'] = strtolower($field['use']);
		return $this->_buildColumn($field);
	}
}

?>