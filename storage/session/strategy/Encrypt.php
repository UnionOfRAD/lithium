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
 * is not stored in cleartext on the client side. You must provide a secret key, otherwise
 * an exception is raised.
 *
 * To use this class, you need to have the `mcrypt` extension enabled.
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
 * By default, this strategy uses the AES algorithm in the CBC mode. This means that an
 * initialization vector has to be generated and transported with the payload data. This
 * is done transparently, but you may want to keep this in mind (the ECB mode doesn't require
 * an itialization vector but is not recommended to use as it's insecure). You can override this
 * defaults by passing a different `cipher` and/or `mode` to the config like this:
 *
 * {{{
 * Session::config(array('default' => array(
 *	'adapter' => 'Cookie',
 *	'strategies' => array('Encrypt' => array(
 *		'cipher' => MCRYPT_RIJNDAEL_128,
 *		'mode' 	 => MCRYPT_MODE_ECB, // Don't use ECB when you don't have to!
 *		'secret'	 => 'foobar'
 *	))
 * )));
 * }}}
 *
 * Please keep in mind that it is generally not a good idea to store sensitive information in
 * cookies (or generally on the client side) and this class is no exception to the rule. It allows
 * you to store client side data in a more secure way, but 100% security can't be achieved.
 *
 * @link http://php.net/manual/en/book.mcrypt.php The mcrypt extension.
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
		if (!static::enabled()) {
			throw new ConfigException("The Mcrypt extension is not installed or enabled.");
		}
		if (!isset($config['secret'])) {
			throw new ConfigException("Encrypt strategy requires a secret key.");
		}
		$defaults = array(
			'cipher' => MCRYPT_RIJNDAEL_256,
			'mode' => MCRYPT_MODE_CBC
		);
		parent::__construct($config + $defaults);

		$cipher = $this->_config['cipher'];
		$mode = $this->_config['mode'];
		$this->_config['vector'] = static::_vector($cipher, $mode);
	}

	/**
	 * Read encryption method.
	 *
	 * @param array $data the Data being read.
	 * @param array $options Options for this method.
	 * @return mixed Returns the decrypted key or the dataset.
	 */
	public function read($data, array $options = array()) {
		$class = $options['class'];

		$encrypted = $class::read(null, array('strategies' => false));
		$key = isset($options['key']) ? $options['key'] : null;

		if (!isset($encrypted['__encrypted']) || !$encrypted['__encrypted']) {
			return isset($encrypted[$key]) ? $encrypted[$key] : null;
		}

		$current = $this->_decrypt($encrypted['__encrypted']);

		if ($key) {
			return isset($current[$key]) ? $current[$key] : null;
		} else {
			return $current;
		}
	}

	/**
	 * Write encryption method.
	 *
	 * @param mixed $data The data to be encrypted.
	 * @param array $options Options for this method.
	 * @return string Returns the written data in cleartext.
	 */
	public function write($data, array $options = array()) {
		$class = $options['class'];

		$futureData = $this->read(null, array('key' => null) + $options) ?: array();
		$futureData = array($options['key'] => $data) + $futureData;

		$payload = empty($futureData) ? null : $this->_encrypt($futureData);

		$class::write('__encrypted', $payload, array('strategies' => false) + $options);
		return $data;
	}

	/**
	 * Delete encryption method.
	 *
	 * @param mixed $data The data to be encrypted.
	 * @param array $options Options for this method.
	 * @return string Returns the deleted data in cleartext.
	 */
	public function delete($data, array $options = array()) {
		$class = $options['class'];

		$futureData = $this->read(null, array('key' => null) + $options) ?: array();
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
	 * @param array $decrypted The cleartext data to be encrypted.
	 * @return string A Base64 encoded and encrypted string.
	 */
	protected function _encrypt($decrypted = array()) {
		$cipher = $this->_config['cipher'];
		$secret = $this->_config['secret'];
		$mode   = $this->_config['mode'];
		$vector = $this->_config['vector'];

		$encrypted = mcrypt_encrypt($cipher, $secret, serialize($decrypted), $mode, $vector);
		$data = base64_encode($encrypted) . base64_encode($vector);

		return $data;
	}

	/**
	 * Decrypt and unserialize a previously encrypted string.
	 *
	 * @param string $encrypted The base64 encoded and encrypted string.
	 * @return array The cleartext data.
	 */
	protected function _decrypt($encrypted) {
		$cipher = $this->_config['cipher'];
		$secret = $this->_config['secret'];
		$mode   = $this->_config['mode'];
		$vector = $this->_config['vector'];

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
	 * @param string $cipher The cipher for the initialization vector.
	 * @param string $mode The mode for the initialization vector.
	 * @return string Returns an initialization vector.
	 * @link http://www.php.net/manual/en/function.mcrypt-create-iv.php
	 */
	protected static function _vector($cipher, $mode) {
		if (static::$_vector) {
			return static::$_vector;
		}

		$size = static::_vectorSize($cipher, $mode);
		return static::$_vector = mcrypt_create_iv($size, MCRYPT_DEV_URANDOM);
	}

	/**
	 * Returns the vector size vor a given cipher and mode.
	 *
	 * @param string $cipher The cipher for the initialization vector.
	 * @param string $mode The mode for the initialization vector.
	 * @return number The vector size.
	 */
	protected static function _vectorSize($cipher, $mode) {
		return mcrypt_get_iv_size($cipher, $mode);
	}
}

?>