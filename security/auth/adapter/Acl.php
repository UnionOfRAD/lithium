<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */
namespace lithium\security\auth\adapter;

//use lithium\core\Libraries;
//use UnexpectedValueException;
//use lithium\security\Password;
use lithium\g11n\Message;
use lithium\security\auth\db\Acos;
use lithium\security\auth\db\Aros;
use lithium\security\auth\db\Permissions;

/**
 * The `Form` adapter provides basic authentication facilities for checking credentials submitted
 * via a web form against a database. To perform an authentication check, the adapter accepts
 * an instance of a `Request` object which contains the submitted form data in its `$data` property.
 *
 * When a request is submitted, the adapter will take the form data from the `Request` object,
 * apply any filters as appropriate (see the `'filters'` configuration setting below), and
 * query a model class using using the filtered data. The data is then checked against any
 * validators configured, which can programmatically check submitted values against database values.
 *
 * By default, the adapter uses a model called `Users`, and lookup fields called `'username'` and
 * `'password'`. These can be customized by setting the `'model'` and `'fields'` configuration keys,
 * respectively. The `'model'` key accepts either a model name (i.e. `Customers`), or a
 * fully-namespaced model class name (i.e. `my_app\models\Customers`). The `'fields'` setting
 * accepts an array of field names to use when looking up a user. An example configuration,
 * including a custom model class and lookup fields might look like the following:
 *
 * {{{
 * Auth::config(array(
 * 	'customer' => array(
 * 		'adapter' => 'Form',
 * 		'model' => 'Customers',
 * 		'fields' => array('email', 'password')
 * 	)
 * ));
 * }}}
 *
 * If the field names present in the form match the fields used in the database lookup, the above
 * will suffice. If, however, the form fields must be matched to different database field names,
 * you can specify an array which matches up the form field names to their corresponding database
 * field names. Suppose, for example, user authentication information in a MongoDB database is
 * nested within a sub-object called `login`. The adapter could be configured as follows:
 *
 * {{{
 * Auth::config(array(
 * 	'customer' => array(
 * 		'adapter' => 'Form',
 * 		'model' => 'Customers',
 * 		'fields' => array('username' => 'login.username', 'password' => 'login.password'),
 * 		'scope' => array('active' => true)
 * 	)
 * ));
 * }}}
 *
 * Note that any additional fields may be specified which should be included in the query. For
 * example, if a user must select a group when logging in, you may override the `'fields'` key with
 * that value as well (i.e. `'fields' => array('username', 'password', 'group')`). If a field is
 * specified which is not present in the request data, the value in the authentication query will be
 * `null`). Note that this will only submit data that is specified in the incoming request. If you
 * would like to further limit the query using fixed conditions, use the `'scope'` key, as shown in
 * the example above.
 *
 * ## Pre-Query Filtering
 *
 * As mentioned, prior to any queries being executed, the request data is modified by any filters
 * configured. Filters are callbacks which accept the value of a submitted form field as input, and
 * return a modified version of the value as output. Filters can be any PHP callable, i.e. a closure
 * or `array('ClassName', 'method')`.
 *
 * For example, if you're doing simple password hashing against a legacy application, you can
 * configure the adapter as follows:
 *
 * {{{
 * Auth::config(array(
 * 	'default' => array(
 * 		'adapter' => 'Form',
 * 		'filters' => array('password' => array('lithium\util\String', 'hash')),
 * 		'validators' => array()
 * 	)
 * ));
 * }}}
 *
 * This applies the default system hash (SHA 512) against the password prior to using it in the
 * query, and overrides `'validators'` to disable the default crypto-based query validation that
 * would occur after the query.
 *
 * Note that if you are specifying the `'fields'` configuration using key / value pairs, the key
 * used to specify the filter must match the key side of the `'fields'` assignment. Additionally,
 * specifying a filter with no key allows the entire data array to be filtered, as in the following:
 *
 * {{{
 * Auth::config(array(
 * 	'default' => array(
 * 		'adapter' => 'Form',
 * 		'filters' => array(function ($data) {
 * 			// Make any modifications to $data, including adding/removing keys
 * 			return $data;
 * 		})
 * 	)
 * ));
 * }}}
 *
 * For more information, see the `_filters()` method or the `$_filters` property.
 *
 * ## Post-Query Validation
 *
 * In addition to filtering data, you can also apply validators to do check submitted form data
 * against database values programmatically. For example, the default adapter uses a cryptographic
 * hash function which operates in constant time to validate passwords. Configuring this validator
 * manually would work as follows:
 *
 * {{{
 * use lithium\security\Password;
 *
 * Auth::config(array(
 * 	'default' => array(
 * 		'adapter' => 'Form',
 * 		'validators' => array(
 * 			'password' => function($form, $data) {
 * 				return Password::check($form, $data);
 * 			}
 * 		)
 * 	)
 * ));
 * }}}
 *
 * As with filters, each validator can be defined as any PHP callable, and must be keyed using the
 * name of the form field submitted (if form and database field names do not match). If a validator
 * is specified with no key, it will apply to all data submitted. See the `$_validators` property
 * and the `_validate()` method for more information.
 *
 * ## Code
 * big thanx for authors of Form, and extensins li3_acces and li3_login
 * @see lithium\net\http\Request::$data
 * @see lithium\data\Model::find()
 * @see lithium\util\String::hash()
 */

class Acl extends \lithium\core\Object {

/**
 * The name of the requester model.
 *
 * @var array
 */
	protected $_model = array();

	/**
	 * List of configuration properties to automatically assign to the properties of the adapter
	 * when the class is constructed.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('creditials', 'model');

	protected static $_session = null;

	protected static $_time = null;

	protected static $_user = null;

/**
 * Sets the initial configuration for the `Form` adapter, as detailed below.
 *
 * @see lithium\security\auth\adapter\Form::$_model
 * @see lithium\security\auth\adapter\Form::$_fields
 * @see lithium\security\auth\adapter\Form::$_filters
 * @see lithium\security\auth\adapter\Form::$_validators
 * @see lithium\security\auth\adapter\Form::$_query
 * @param array $config Sets the configuration for the adapter, which has the following options:
 *              - `'model'` _string_: The name of the model class to use. See the `$_model`
 *                property for details.
 *              - `'fields'` _array_: The model fields to query against when taking input from
 *                the request data. See the `$_fields` property for details.
 *              - `'scope'` _array_: Any additional conditions used to constrain the
 *                authentication query. For example, if active accounts in an application have
 *                an `active` field which must be set to `true`, you can specify
 *                `'scope' => array('active' => true)`. See the `$_scope` property for more
 *                details.
 *              - `'filters'` _array_: Named callbacks to apply to request data before the user
 *                lookup query is generated. See the `$_filters` property for more details.
 *              - `'validators'` _array_: Named callbacks to apply to fields in request data and
 *                corresponding fields in database data in order to do programmatic
 *                authentication checks after the query has occurred. See the `$_validators`
 *                property for more details.
 *              - `'query'` _string_: Determines the model method to invoke for authentication
 *                checks. See the `$_query` property for more details.
 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'creditials' => 'default',
			'model' => 'Users',
			'acl' => array(
				'Aros' => 'aros',
				'Acos' => 'acos',
				'Permissions' => 'aros_acos'
			)
		);
		$config += $defaults;

//		$password = function($form, $data) {
//			return Password::check($form, $data);
//		};
//		$config['validators'] += compact('password');

		parent::__construct($config + $defaults);
	}

	/**
	 * Initializes values configured in the constructor.
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();

		foreach ($this->_fields as $key => $val) {
			if (is_int($key)) {
				unset($this->_fields[$key]);
				$this->_fields[$val] = $val;
			}
		}
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
		// do cred przekazujemy nazwe konfiruracji autha ktora dokonala autoryzacji
		// do aro przekazjemy szczegoly
		//$model = $this->_model;
		//$query = $this->_query;
		//$data = $this->_filters($credentials->data);
//		if (empty($credentials)) {
//			throw new ConfigException('No credentials defined for adapter.');
//		}
		// 1. take session
		if ($requester = Session::read($credentials)) {
			self::$_session = $requester;
			if(!isset($options['aro']) && empty($options['aro'])){
				$options['aro'] = array(
						//'parent_id' => NULL,
						'model' => $this->_model,
						'foreign_key' => $requester->id
				);
			}
		}
		//$conditions = $this->_scope + array_diff_key($data, $this->_validators);
		// 2. take aro aco node
		if (!$user) {
			return false;
		}
		return self::_check($options['aro'], $options['aco']);
		//return $this->_validate($user, $data);
	}

/**
 * Checks if the given $aro has access to action $action in $aco
 *
 * @param string $aro ARO The requesting object identifier.
 * @param string $aco ACO The controlled object identifier.
 * @param string $action Action (defaults to *)
 * @return boolean Success (true if ARO has access to action in ACO, false otherwise)
 * @access public
 * @link http://book.cakephp.org/view/1249/Checking-Permissions-The-ACL-Component
 */
	private static function _check($aro, $aco, $action = "*") {
		if ($aro == null || $aco == null) {
			return false;
		}

		//$permKeys = $this->_getAcoKeys($this->Aros->Permission->schema());
		$aroPath = Aros::node($aro);
		$acoPath = Acos::node($aco);

		if (empty($aroPath) || empty($acoPath)) {
			throw new \Exception($t("Auth\Acl::check() - Failed ARO/ACO node lookup in permissions check.  Node references:\nAro: ", true) . print_r($aro, true) . "\nAco: " . print_r($aco, true));
			return false;
		}

		if ($acoPath == null || $acoPath == array()) {
			throw new \Exception($t("Auth\Acl::check() - Failed ACO node lookup in permissions check.  Node references:\nAro: ", true) . print_r($aro, true) . "\nAco: " . print_r($aco, true));
			return false;
		}

		$aroNode = $aroPath[0];
		$acoNode = $acoPath[0];

//		if ($action != '*' && !in_array('_' . $action, $permKeys)) {
//			trigger_error(sprintf(__("ACO permissions key %s does not exist in DbAcl::check()", true), $action), E_USER_NOTICE);
//			return false;
//		}

		$inherited = array();
		$acoIDs = Set::extract($acoPath, '{n}.' . $this->Aco->alias . '.id');

		$count = count($aroPath);
		for ($i = 0 ; $i < $count; $i++) {
			$permAlias = $this->Aro->Permission->alias;

			$perms = $this->Aro->Permission->find('all', array(
				'conditions' => array(
					"{$permAlias}.aro_id" => $aroPath[$i][$this->Aro->alias]['id'],
					"{$permAlias}.aco_id" => $acoIDs
				),
				'order' => array($this->Aco->alias . '.lft' => 'desc'),
				'recursive' => 0
			));

			if (empty($perms)) {
				continue;
			} else {
				$perms = Set::extract($perms, '{n}.' . $this->Aro->Permission->alias);
				foreach ($perms as $perm) {
//					if ($action == '*') {

						foreach ($permKeys as $key) {
							if (!empty($perm)) {
								if ($perm[$key] == -1) {
									return false;
								} elseif ($perm[$key] == 1) {
									$inherited[$key] = 1;
								}
							}
						}

						if (count($inherited) === count($permKeys)) {
							return true;
						}
//					} else {
//						switch ($perm['_' . $action]) {
//							case -1:
//								return false;
//							case 0:
//								continue;
//							break;
//							case 1:
//								return true;
//							break;
//						}
//					}
				}
			}
		}
		return false;
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
}
?>