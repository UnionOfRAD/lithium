<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\security;

class Auth extends \lithium\core\Adaptable {

	/**
	 * Stores configurations for various authentication adapters.
	 *
	 * @var object `Collection` of authentication configurations.
	 */
	protected static $_configurations;

	protected static $_adapters = 'adapter.security.auth';

	protected static $_classes = array(
		'session' => '\lithium\storage\Session'
	);

	protected static function _initConfig($name, $config) {
		$defaults = array(
			'sessionKey' => $name,
			'sessionClass' => static::$_classes['session']
		);
		return parent::_initConfig($name, $config) + $defaults;
	}

	public static function check($name, $credentials, $options = array()) {
		$defaults = array('session' => true);
		$options += $defaults;

		if ($user = static::adapter($name)->check($credentials, $options)) {
			return true;
		}
		return false;
	}
}

?>