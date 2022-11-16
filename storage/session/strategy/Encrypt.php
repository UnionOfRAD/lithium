<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2011, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\session\strategy;

use lithium\core\AutoConfigurable;
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
 * @link http://php.net/book.openssl.php
 */
class Encrypt {

	use AutoConfigurable;

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
		if (!extension_loaded('openssl')) {
			throw new ConfigException('The `openssl` extension is not installed or enabled.');
		}
		$this->_autoConfig($config + $this->_defaults, []);
		$this->_autoInit($config);
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
		$current = $this->_decrypt($encrypted['__encrypted']);

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
			$payload = $this->_encrypt($futureData);
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
			$payload = $this->_encrypt($futureData);
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
}

?>