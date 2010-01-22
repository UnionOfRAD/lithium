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

	/**
	 * The path used by `Libraries::locate()` to look up adapter classes for `Auth`.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string
	 */
	protected static $_adapters = 'adapter.security.auth';

	protected static $_classes = array(
		'session' => '\lithium\storage\Session'
	);

	protected static function _initConfig($name, $config) {
		$defaults = array('session' => array(
			'key' => $name,
			'class' => static::$_classes['session'],
			'options' => array()
		));
		$config = parent::_initConfig($name, $config) + $defaults;
		$config['session'] += $defaults['session'];
		return $config;
	}

	public static function check($name, $credentials = null, $options = array()) {
		$defaults = array('checkSession' => true);
		$options += $defaults;
		$config = static::_config($name);
		$session = $config['session'];

		if ($options['checkSession']) {
			if ($data = $session['class']::read($session['key'], $session['options'])) {
				return $data;
			}
		}

		if ($data = static::adapter($name)->check($credentials, $options)) {
			$session['class']::write($session['key'], $data, $session['options']);
			return $data;
		}
		return false;
	}

	public static function clear($name, $options = array()) {
		$defaults = array('clearSession' => true);
		$options += $defaults;
		$config = static::_config($name);
		$session = $config['session'];

		if ($options['clearSession']) {
			$session['class']::delete($session['key'], $session['options']);
		}
		static::adapter($name)->clear($options);
	}
}

?>