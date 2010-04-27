<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\security\auth\adapter;

use \lithium\core\Libraries;

/**
 * The `Form` adapter provides basic authentication facilities for checking credentials submitted
 * via a web form against a database. To perform an authentication check, the adapter accepts
 * an instance of a `Request` object which contains the submitted form data in its `$data` property.
 *
 * When a request is submitted, the adapter will take the form data from the `Request` object,
 * apply any filters as appropriate (see the `'filters'` configuration setting below), and
 * query a model class using using the filtered data.
 *
 * By default, the adapter uses a model called `User`, and lookup fields called `'username'` and
 * `'password'`. These can be customized by setting the `'model'` and `'fields'` configuration keys,
 * respectively. The `'model'` key accepts either a model name (i.e. `Customer`), or a
 * fully-namespaced path to a model class (i.e. `\app\models\Customer`). The `'fields'` setting
 * accepts an array of field names to use when looking up a user. An example configuration,
 * including a custom model class and lookup fields might look like the following:
 * {{{
 * 	Auth::config(array(
 * 		'customer' => array(
 * 			'model' => 'Customer',
 * 			'fields' => array('email', 'password')
 * 		)
 * 	));
 * }}}
 *
 * If the field names present in the form match the fields used in the database lookup, the above
 * will suffice. If, however, the form fields must be matched to non-standard database column names,
 * you can specify an array which matches up the form field names to their corresponding database
 * column names. Suppose, for example, user authentication information in a MongoDB database is
 * nested within a sub-object called `login`. The adapter could be configured as follows:
 * {{{
 * 	Auth::config(array(
 * 		'customer' => array(
 * 			'model' => 'Customer',
 * 			'fields' => array('username' => 'login.username', 'password' => 'login.password'),
 * 			'scope' => array('active' => true)
 * 		)
 * 	));
 * }}}
 *
 * Note that any additional fields may be specified which should be included in the query. For
 * example, if a user must select a group when logging in, you may override the `'fields'` key with
 * that value as well (i.e. `'fields' => array('username', 'password', 'group')`; note that if a
 * field is specified which is not present in the request, the value in the query will be `null`).
 * However, this will only submit data that is specified in the incoming request. If you would like
 * to further limit the query using fixed data, use the `'scope'` key, as shown in the example
 * above.
 *
 * As mentioned, prior to any queries being executed, the request data is modified by any filters
 * configured. Filters are callbacks which accept the value of a field as input, and return a
 * modified version of the value as output. Filters can be any PHP callable, i.e. a closure or
 * `array('ClassName', 'method')`. The only filter that is configured by default is for
 * the password field, which is filtered by `lithium\util\String::hash()`.
 *
 * Note that if you are specifying the `'fields'` configuration using key/value pairs, the key
 * used to specify the filter must match the key side of the `'fields'` assignment.
 *
 * @see lithium\net\http\Request::$data
 * @see lithium\data\Model::find()
 * @see lithium\util\String::hash()
 */
class Form extends \lithium\core\Object {

	/**
	 * The name of the model class to query against. This can either be a model name (i.e.
	 * `'User'`), or a fully-namespaced class reference (i.e. `'app\models\User'`). When
	 * authenticating users, the magic `first()` method is invoked against the model to return the
	 * first record found when combining the conditions in the `$_scope` property with the
	 * authentication data yielded from the `Request` object in `Form::check()`. (Note that the
	 * model method called is configurable using the `$_query` property).
	 *
	 * @see lithium\security\auth\adapter\Form::$_query
	 * @var string
	 */
	protected $_model = '';

	/**
	 * The list of fields to extract from the `Request` object and use when querying the database.
	 * This can either be a simple array of field names, or a set of key/value pairs, which map
	 * the field names in the request to
	 *
	 * @var array
	 */
	protected $_fields = array();

	/**
	 * Additional data to apply to the model query conditions when looking up users, i.e.
	 * `array('active' => true)` to disallow authenticating against inactive user accounts.
	 *
	 * @var array
	 */
	protected $_scope = array();

	/**
	 * Callback filters to apply to request data before using it the authentication query. Each key
	 * in the array must match a request field specified in the `$_fields` property, and each
	 * value must either be a reference to a function or method name, or a closure. For example, to
	 * automatically hash passwords, the `Form` adapter provides the following default
	 * configuration, i.e.: `array('password' => array('\lithium\util\String', 'hash'))`.
	 *
	 * Optionally, you can specify a callback with no key, which will receive (and can modify) the
	 * entire credentials array before the query is executed, as in the following example:
	 * {{{
	 * 	Auth::config(array(
	 * 		'members' => array(
	 * 			'model' => 'Member',
	 * 			'fields' => array('email', 'password'),
	 * 			'filters' => array(function($data) {
	 * 				// If the user is outside the company, then their account must have the
	 * 				// 'private' field set to true in order to log in:
	 * 				if (!preg_match('/@mycompany\.com$/', $data['email'])) {
	 * 					$data['private'] = true;
	 * 				}
	 * 				return $data;
	 * 			})
	 * 		)
	 * 	));
	 * }}}
	 *
	 *
	 * @see lithium\security\auth\adapter\Form::$_fields
	 * @var array
	 */
	protected $_filters = array('password' => array('\lithium\util\String', 'hash'));

	/**
	 * If you require custom model logic in your authentication query, use this setting to specify
	 * which model method to call, and this method will receive the authentication query. In return,
	 * the `Form` adapter expects a `Record` object which implements the `data()` method. See the
	 * constructor for more information on setting this property. Defaults to `'first'`, which
	 * calls, for example, `User::first()`.
	 *
	 * @see lithium\security\auth\adapter\Form::__construct()
	 * @see lithium\data\model\Record::data()
	 * @var string
	 */
	protected $_query = '';

	/**
	 * List of configuration properties to automatically assign to the properties of the adapter
	 * when the class is constructed.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('model', 'fields', 'scope', 'filters' => 'merge', 'query');

	/**
	 * Sets the initial configuration for the `Form` adapter, as detailed below.
	 *
	 * @see lithium\security\auth\adapter\Form::$_model
	 * @see lithium\security\auth\adapter\Form::$_fields
	 * @see lithium\security\auth\adapter\Form::$_filters
	 * @see lithium\security\auth\adapter\Form::$_query
	 * @param array $config Sets the configuration for the adapter, which has the following options:
	 *              - `'model'` _string_: The name of the model class to use. See the `$_model`
	 *                property for details.
	 *              - `'fields'` _array_: The model fields to query against when taking input from
	 *                the request data. See the `$_fields` property for details.
	 *              - `'filters'` _array_: Named callbacks to apply to request data before the user
	 *                lookup query is generated. See the `$_filters` property for more details.
	 *              - `'query'` _string_: Determines the model method to invoke for authentication
	 *                checks. See the `$_query` property for more details.
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'model' => 'User', 'query' => 'first', 'filters' => array(), 'fields' => array(
				'username', 'password'
			)
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Called by the `Auth` class to run an authentication check against a model class using the
	 * credientials in a data container (a `Request` object), and returns an array of user
	 * information on success, or `false` on failure.
	 *
	 * @param object $credentials A data container which wraps the authentication credentials used
	 *               to query the model (usually a `Request` object). See the documentation for this
	 *               class for further details.
	 * @param array $options Additional configuration options. Not currently implemented in this
	 *              adapter.
	 * @return array Returns an array containing user information on success, or `false` on failure.
	 */
	public function check($credentials, array $options = array()) {
		$model = $this->_model;
		$query = $this->_query;
		$conditions = $this->_scope + $this->_filters($credentials->data);
		$user = $model::$query(compact('conditions'));
		return $user ? $user->data() : false;
	}

	/**
	 * A pass-through method called by `Auth`. Returns the value of `$data`, which is written to
	 * a user's session. When implementing a custom adapter, this method may be used to modify or
	 * reject data before it is written to the session.
	 *
	 * @param array $data User data to be written to the session.
	 * @param array $options Adapter-specific options. Not implemented in the `Form` adapter.
	 * @return array Returns the value of `$data`.
	 */
	public function set($data, array $options = array()) {
		return $data;
	}

	/**
	 * Called by `Auth` when a user session is terminated. Not implemented in the `Form` adapter.
	 *
	 * @param array $options Adapter-specific options. Not implemented in the `Form` adapter.
	 * @return void
	 */
	public function clear(array $options = array()) {
	}

	protected function _init() {
		parent::_init();

		if (isset($this->_fields[0])) {
			$this->_fields = array_combine($this->_fields, $this->_fields);
		}
		$this->_model = Libraries::locate('models', $this->_model);
	}

	/**
	 * Calls each registered callback, by field name.
	 *
	 * @param string $data Keyed form data.
	 * @return mixed Callback result.
	 */
	protected function _filters($data) {
		$result = array();

		foreach ($this->_fields as $key => $field) {
			$result[$field] = isset($data[$key]) ? $data[$key] : null;

			if (isset($this->_filters[$key])) {
				$result[$field] = call_user_func($this->_filters[$key], $result[$field]);
			}
		}
		return isset($this->_filters[0]) ? call_user_func($this->_filters[0], $result) : $result;
	}
}

?>