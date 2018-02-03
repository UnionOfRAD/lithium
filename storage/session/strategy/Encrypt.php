<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\session\strategy;

use lithium\core\ConfigException;
use lithium\security\Random;

/**
 * This strategy allows you to encrypt your `Session` and / or `Cookie` data so that it
 * is not stored in cleartext on the client side. You must provide a secret key, otherwise
 * an exception is raised.
 *
 * To use this class, you need to have the `openssl` extension enabled.
 *
 * Example configuration:
 *
 * ```
 * Session::config(['default' => [
 *    'adapter' => 'Cookie',
 *    'strategies' => ['Encrypt' => ['secret' => 'f00bar$l1thium']]
 * ]]);
 * ```
 *
 * By default, this strategy uses the AES algorithm in the CBC mode. This means that an
 * initialization vector has to be generated and transported with the payload data. This
 * is done transparently, but you may want to keep this in mind (the ECB mode doesn't require
 * an initialization vector but is not recommended to use as it's insecure).
*
 * Please keep in mind that it is generally not a good idea to store sensitive information in
 * cookies (or generally on the client side) and this class is no exception to the rule. It allows
 * you to store client side data in a more secure way, but 100% security can't be achieved.
 *
 * Also note that if you provide a secret that is shorter than the maximum key length of the
 * algorithm used, the secret will be hashed to make it more secure. This also means that if you
 * want to use your own hashing algorithm, make sure it has the maximum key length of the algorithm
 * used. See the `Encrypt::_hashSecret()` method for more information on this.
 *
 * ## Legacy Mode
 *
 * This class previously used the now deprecated `mcrypt` extension and has been migrated
 * to use of the `openssl` extension for better support and performance. For backwards
 * compatibility reasons this class supports a _legacy_ mode in which `mcrypt` will be
 * used. The class will switch to legacy mode whenever it is not possible to use openssl
 * as a drop in.
 *
 * First, legacy mode will be triggered when the `openssl` extension is not available.
 *
 * Second, previously overriding the default cipher and mode were possible (see example
 * below). As we only support AES-256 in CBC mode (equals `mcrypt`'s RIJNDAEL_128 with
 * MODE_CBC) with the `openssl` extension, overrding the defaults will trigger legacy
 * mode.
 *
 * ```
 * Session::config(['default' => [
 *     'adapter' => 'Cookie',
 *     'strategies' => ['Encrypt' => [
 *         'cipher' => MCRYPT_RIJNDAEL_256,
 *         'mode' => MCRYPT_MODE_ECB, // Don't use ECB when you don't have to!
 *         'secret' => 'f00bar$l1thium'
 *     ]]
 * ]]);
 * ```
 *
 * @link http://php.net/book.openssl.php
 * @link http://php.net/book.mcrypt.php The mcrypt extension.
 * @link http://php.net/mcrypt.ciphers.php List of supported ciphers.
 * @link http://php.net/mcrypt.constants.php List of supported modes.
 */
class Encrypt extends \lithium\core\Object {

	/**
	 * Default configuration.
	 */
	protected $_defaults = [
		'secret' => null
	];

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration array.
	 * @return void
	 */
	public function __construct(array $config = []) {
		if (!isset($config['secret'])) {
			throw new ConfigException('Encrypt strategy requires a secret key.');
		}

		if ($this->_mcrypt = $this->_mcrypt($config)) {
			if (!extension_loaded('mcrypt')) {
				throw new ConfigException('The mcrypt extension is not installed or enabled.');
			}
			parent::__construct($config + $this->_defaults + [
				'cipher' => MCRYPT_RIJNDAEL_128,
				'mode' => MCRYPT_MODE_CBC
			]);
			$this->_mcryptResource = mcrypt_module_open(
				$this->_config['cipher'], '', $this->_config['mode'], ''
			);
		} else {
			if (!extension_loaded('openssl')) {
				throw new ConfigException('The `openssl` extension is not installed or enabled.');
			}
			parent::__construct($config + $this->_defaults);
		}
	}

	/**
	 * Read encryption method.
	 *
	 * @param array $data the Data being read.
	 * @param array $options Options for this method.
	 * @return mixed Returns the decrypted data after it was read.
	 */
	public function read($data, array $options = []) {
		$class = $options['class'];

		$encrypted = $class::read(null, ['strategies' => false]);
		$key = isset($options['key']) ? $options['key'] : null;

		if (!isset($encrypted['__encrypted']) || !$encrypted['__encrypted']) {
			return isset($encrypted[$key]) ? $encrypted[$key] : null;
		}

		if ($this->_mcrypt) {
			$current = $this->_bcDecrypt($encrypted['__encrypted']);
		} else {
			$current = $this->_decrypt($encrypted['__encrypted']);
		}

		if ($key) {
			return isset($current[$key]) ? $current[$key] : null;
		}
		return $current;
	}

	/**
	 * Write encryption method.
	 *
	 * @param mixed $data The data to be encrypted.
	 * @param array $options Options for this method.
	 * @return string Returns the encrypted data that was written.
	 */
	public function write($data, array $options = []) {
		$class = $options['class'];

		$futureData = $this->read(null, ['key' => null] + $options) ?: [];
		$futureData = [$options['key'] => $data] + $futureData;

		$payload = null;

		if (!empty($futureData)) {
			if ($this->_mcrypt) {
				$payload = $this->_bcEncrypt($futureData);
			} else {
				$payload = $this->_encrypt($futureData);
			}
		}

		$class::write('__encrypted', $payload, ['strategies' => false] + $options);
		return $payload;
	}

	/**
	 * Delete encryption method.
	 *
	 * @param mixed $data The data to be encrypted.
	 * @param array $options Options for this method.
	 * @return string Returns the deleted data in cleartext.
	 */
	public function delete($data, array $options = []) {
		$class = $options['class'];

		$futureData = $this->read(null, ['key' => null] + $options) ?: [];
		unset($futureData[$options['key']]);

		$payload = null;

		if (!empty($futureData)) {
			if ($this->_mcrypt) {
				$payload = $this->_bcEncrypt($futureData);
			} else {
				$payload = $this->_encrypt($futureData);
			}
		}

		$class::write('__encrypted', $payload, ['strategies' => false] + $options);
		return $data;
	}

	/**
	 * Serialize and encrypt a given data array.
	 *
	 * @param array $decrypted The cleartext data to be encrypted.
	 * @return string A Base64 encoded and encrypted string.
	 */
	protected function _encrypt($decrypted = []) {
		$encrypted = openssl_encrypt(
			serialize($decrypted),
			'aes-256-cbc',
			$this->_hashSecret($this->_config['secret']),
			OPENSSL_RAW_DATA,
			$vector = $this->_vector()
		);
		return base64_encode($encrypted) . base64_encode($vector);
	}

	/**
	 * Decrypt and unserialize a previously encrypted string.
	 *
	 * @param string $encrypted The base64 encoded and encrypted string.
	 * @return array The cleartext data.
	 */
	protected function _decrypt($encrypted) {
		$secret = $this->_hashSecret($this->_config['secret']);

		$vectorSize = strlen(base64_encode(str_repeat(' ', $this->_vectorSize())));
		$vector = base64_decode(substr($encrypted, -$vectorSize));
		$data = base64_decode(substr($encrypted, 0, -$vectorSize));

		$decrypted = openssl_decrypt(
			$data,
			'aes-256-cbc',
			$secret,
			OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING,
			$vector
		);
		return unserialize(trim($decrypted));
	}

	/**
	 * Determines if the `mcrypt` or `openssl` extension has been installed.
	 *
	 * @return boolean `true` if enabled, `false` otherwise.
	 */
	public static function enabled() {
		return extension_loaded('openssl') || extension_loaded('mcrypt');
	}

	/**
	 * Hashes the given secret to make harder to detect.
	 *
	 * This method figures out the appropriate key size for the chosen encryption algorithm and
	 * then hashes the given key accordingly. Note that if the key has already the needed length,
	 * it is considered to be hashed (secure) already and is therefore not hashed again. This lets
	 * you change the hashing method in your own code if you like.
	 *
	 * The default `aes-256-cbc` key should be 32 byte long `sha256` is used as the
	 * hashing algorithm. If the key size is shorter than the one generated by `sha256`,
	 * the first n bytes will be used.
	 *
	 * @param string $key The possibly too weak key.
	 * @return string The hashed (raw) key.
	 */
	protected function _hashSecret($key) {
		if (strlen($key) >= 32) {
			return $key;
		}
		return substr(hash('sha256', $key, true), 0, 32);
	}

	/**
	 * Generates an initialization vector.
	 *
	 * @return string Returns an initialization vector.
	 */
	protected function _vector() {
		return Random::generate($this->_vectorSize());
	}

	/**
	 * Returns the vector size.
	 *
	 * @return integer The vector size in bytes.
	 */
	protected function _vectorSize() {
		return openssl_cipher_iv_length('aes-256-cbc');
	}

	/* Deprecated / BC */

	/**
	 * Indicates if we are in legacy / BC mode and the class is using the mcrypt extension
	 * or we are able to use the openssl extension.
	 *
	 * @deprecated
	 */
	protected $_mcrypt = false;

	/**
	 * Holds the mcrypt crypto resource after initialization, when in legacy mode.
	 *
	 * @deprecated
	 */
	protected $_mcryptResource = null;

	/**
	 * Checks for legacy mode.
	 *
	 * @deprecated
	 * @param array $config
	 * @return boolean
	 */
	protected function _mcrypt(array $config) {
		if (isset($config['cipher']) || isset($config['mode'])) {
			$message  = "You've selected a non-default cipher and/or mode configuration. ";
			$message .= "The Encrypt strategy is now in legacy mode and will use the ";
			$message .= "deprecated mcrypt extension. To disable legacy mode, use the strategy ";
			$message .= "with default configuration.";
			trigger_error($message, E_USER_DEPRECATED);

			return true;
		}
		if (!extension_loaded('openssl')) {
			$message .= "The Encrypt strategy is now in legacy mode and will use the ";
			$message .= "deprecated mcrypt extension. To disable legacy mode, install the ";
			$message .= "openssl extension.";
			trigger_error($message, E_USER_DEPRECATED);

			return true;
		}
		return false;
	}

	/**
	 * Destructor. Closes the crypto resource when it is no longer needed.
	 *
	 * @deprecated
	 * @return void
	 */
	public function __destruct() {
		if (is_resource($this->_mcryptResource)) {
			mcrypt_module_close($this->_mcryptResource);
		}
	}

	/**
	 * Serialize and encrypt a given data array.
	 *
	 * @deprecated
	 * @param array $decrypted The cleartext data to be encrypted.
	 * @return string A Base64 encoded and encrypted string.
	 */
	protected function _bcEncrypt($decrypted = []) {
		$vector = $this->_bcVector();
		$secret = $this->_bcHashSecret($this->_config['secret']);

		mcrypt_generic_init($this->_mcryptResource, $secret, $vector);
		$encrypted = mcrypt_generic($this->_mcryptResource, serialize($decrypted));
		mcrypt_generic_deinit($this->_mcryptResource);

		return base64_encode($encrypted) . base64_encode($vector);
	}

	/**
	 * Decrypt and unserialize a previously encrypted string.
	 *
	 * @deprecated
	 * @param string $encrypted The base64 encoded and encrypted string.
	 * @return array The cleartext data.
	 */
	protected function _bcDecrypt($encrypted) {
		$secret = $this->_hashSecret($this->_config['secret']);

		$vectorSize = strlen(base64_encode(str_repeat(" ", $this->_bcVectorSize())));
		$vector = base64_decode(substr($encrypted, -$vectorSize));
		$data = base64_decode(substr($encrypted, 0, -$vectorSize));

		mcrypt_generic_init($this->_mcryptResource, $secret, $vector);
		$decrypted = mdecrypt_generic($this->_mcryptResource, $data);
		mcrypt_generic_deinit($this->_mcryptResource);

		return unserialize(trim($decrypted));
	}

	/**
	 * Hashes the given secret to make harder to detect.
	 *
	 * This method figures out the appropriate key size for the chosen encryption algorithm and
	 * then hashes the given key accordingly. Note that if the key has already the needed length,
	 * it is considered to be hashed (secure) already and is therefore not hashed again. This lets
	 * you change the hashing method in your own code if you like.
	 *
	 * The default `MCRYPT_RIJNDAEL_128` key should be 32 byte long `sha256` is used
	 * as the hashing algorithm. If the key size is shorter than the one generated by
	 * `sha256`, the first n bytes will be used.
	 *
	 * @deprecated
	 * @link http://php.net/function.mcrypt-enc-get-key-size.php
	 * @param string $key The possibly too weak key.
	 * @return string The hashed (raw) key.
	 */
	protected function _bcHashSecret($key) {
		$size = mcrypt_enc_get_key_size($this->_mcryptResource);

		if (strlen($key) >= $size) {
			return $key;
		}
		return substr(hash('sha256', $key, true), 0, $size);
	}

	/**
	 * Generates an initialization vector.
	 *
	 * @deprecated
	 * @link http://php.net/function.mcrypt-create-iv.php
	 * @return string Returns an initialization vector.
	 */
	protected function _bcVector() {
		return mcrypt_create_iv($this->_bcVectorSize(), MCRYPT_DEV_URANDOM);
	}

	/**
	 * Returns the vector size vor a given cipher and mode.
	 *
	 * @deprecated
	 * @link http://php.net/function.mcrypt-enc-get-iv-size.php
	 * @return number The vector size.
	 */
	protected function _bcVectorSize() {
		return mcrypt_enc_get_iv_size($this->_mcryptResource);
	}
}

?>