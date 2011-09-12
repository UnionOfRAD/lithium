<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\strategy;

use lithium\core\ConfigException;

/**
 * This strategy allows you to encrypt your `Session` and / or `Cookie` data so that it
 * is not stored in cleartext on the client side.
 *
 * Example configuration:
 *
 * {{{
 * Session::config(array('default' => array(
 *	'adapter' => 'Cookie',
 *	'strategies' => array('Encrypt' => array('secret' => 'foobar'))
 * )));
 * }}}
 *
 * By default, this strategy uses the AES algorithm in the CBC mode. You can override this
 * defaults by passing a different `cipher` and/or `mode` to the config like this:
 *
 * {{{
 * Session::config(array('default' => array(
 *	'adapter' => 'Cookie',
 *	'strategies' => array('Encrypt' => array(
 *		'cipher' => MCRYPT_RIJNDAEL_128,
 *		'mode' 	 => MCRYPT_MODE_ECB,
 *		'secret'	 => 'foobar'
 *	))
 * )));
 * }}}
 *
 * @link http://www.php.net/manual/en/mcrypt.ciphers.php List of supported ciphers.
 * @link http://www.php.net/manual/en/mcrypt.constants.php List of supported modes.
 */
class Encrypt extends \lithium\core\Object {

	/**
	 * Holds the initialization vector.
	 */
	protected static $_vector = null;

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration array. You can override the default cipher and mode.
	 */
	public function __construct(array $config = array()) {
		if (!isset($config['secret'])) {
			throw new ConfigException("Encrypt strategy requires a secret key.");
		}
		$defaults = array(
			'cipher' => MCRYPT_RIJNDAEL_256,
			'mode' => MCRYPT_MODE_CBC
		);
		parent::__construct($config + $defaults);
		$this->_config['vector'] = static::_vector($this->_config['cipher'], $this->_config['mode']);
	}
	
	/**
	 * Read encryption method.
	 *
	 * @param
	 * @param
	 * @return
	 */
	public function read($data, array $options = array()) {
		$class = $options['class'];
		
		$encrypted = $class::read(null, array('strategies' => false));
		
		if (!isset($encrypted['__encrypted']) || !$encrypted['__encrypted']) {
			return isset($encrypted[$data]) ? $encrypted[$data] : null;
		}
		
		$current = $this->_decrypt($encrypted['__encrypted']);
		
		if($data) {
			return isset($current[$data]) ? $current[$data] : null;
		} else {
			return $current;
		}
	}

	/**
	 * Write encryption method.
	 *
	 * @param
	 * @param
	 * @return
	 */
	public function write($data, array $options = array()) {
		$class = $options['class'];

		$futureData = $this->read(null, $options) ?: array();
		$futureData = array($options['key'] => $data) + $futureData;

		$payload = empty($futureData) ? null : $this->_encrypt($futureData);

		$class::write('__encrypted', $payload, array('strategies' => false) + $options);
		return $data;
	}

	/**
	 * Delete encryption method.
	 *
	 * @param
	 * @param
	 * @return
	 */
	public function delete($data, array $options = array()) {
		$class = $options['class'];

		$futureData = $this->read(null, $options) ?: array();
		unset($futureData[$options['key']]);
		
		$payload = empty($futureData) ? null : $this->_encrypt($futureData);

		$class::write('__encrypted', $payload, array('strategies' => false) + $options);
		return $data;
	}

	/**
	 * Determines if the Mcrypt extension has been installed.
	 *
	 * @return boolean `true` if enabled, `false` otherwise
	 */
	public static function enabled() {
		return extension_loaded('mcrypt');
	}

	/**
	 * Serialize and encrypt a given data array.
	 *
	 * @param
	 * @return
	 */
	protected function _encrypt($decrypted = array()) {
		extract($this->_config);
		
		$encrypted = mcrypt_encrypt($cipher, $secret, serialize($decrypted), $mode, $vector);
		$data = base64_encode($encrypted) . base64_encode($vector);
		
		return $data;
	}

	/**
	 * Decrypt and unserialize a previously encrypted string.
	 *
	 * @param
	 * @return
	 */
	protected function _decrypt($encrypted) {
		extract($this->_config);
		
		$vectorSize = strlen(base64_encode(str_repeat(" ", static::_vectorSize($cipher, $mode))));
		$vector = base64_decode(substr($encrypted, -$vectorSize));
		$data = base64_decode(substr($encrypted, 0, -$vectorSize));

		$decrypted = mcrypt_decrypt($cipher, $secret, $data, $mode, $vector);
		$data = unserialize(trim($decrypted));

		return $data;
	}

	/**
	 * Generates an initialization vector.
	 *
	 * @param
	 * @param
	 * @return string Returns an initialization vector.
	 * @link http://www.php.net/manual/en/function.mcrypt-create-iv.php
	 */
	protected static function _vector($cipher, $mode) {
		if(static::$_vector) {
			return static::$_vector;
		}

		$size = static::_vectorSize($cipher, $mode);
		return static::$_vector = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
	}

	/**
	 * Returns the vector size vor a given cipher and mode.
	 *
	 * @param
	 * @param
	 * @return
	 */
	protected static function _vectorSize($cipher, $mode) {
		return mcrypt_get_iv_size($cipher, $mode);
	}
}