<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\storage\session\strategy;

use RuntimeException;
use lithium\core\ConfigException;
use lithium\storage\session\strategy\MissingSignatureException;
use lithium\security\Hash;

/**
 * This strategy allows you to sign your `Session` and / or `Cookie` data with a passphrase
 * to ensure that it has not been tampered with.
 *
 * Example configuration:
 *
 * ```
 * Session::config(['default' => [
 *    'adapter' => 'Cookie',
 *    'strategies' => ['Hmac' => ['secret' => 'foobar']]
 * ]]);
 * ```
 *
 * This will configure the `HMAC` strategy to be used for all `Session` operations with the
 * `default` named configuration. A hash-based message authentication code (HMAC) will be
 * calculated for all data stored in your cookies, and will be compared to the signature
 * stored in your cookie data. If the two do not match, then your data has been tampered with
 * (or you have modified the data directly _without_ passing through the `Session` class, which
 * amounts to the same), then a catchable `RuntimeException` is thrown.
 *
 * Please note that this strategy is very finnicky, and is so by design. If you attempt to access
 * or modify the stored data in any way other than through the `Session` class configured with the
 * `Hmac` strategy with the properly configured `secret`, then it will probably blow up.
 *
 * @link http://en.wikipedia.org/wiki/HMAC Wikipedia: Hash-based Message Authentication Code
 */
class Hmac {

	/**
	 * The HMAC secret.
	 *
	 * @var string HMAC secret string.
	 */
	protected static $_secret = null;

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration array. Will throw an exception if the 'secret'
	 *        configuration key is not set.
	 * @return void
	 */
	public function __construct(array $config = []) {
		if (!isset($config['secret'])) {
			throw new ConfigException("HMAC strategy requires a secret key.");
		}
		static::$_secret = $config['secret'];
	}

	/**
	 * Write strategy method.
	 *
	 * Adds an HMAC signature to the data. Note that this will transform the
	 * passed `$data` to an array, and add a `__signature` key with the HMAC-calculated
	 * value.
	 *
	 * @see lithium\storage\Session
	 * @see lithium\core\Adaptable::config()
	 * @link http://php.net/function.hash-hmac.php PHP Manual: hash_hmac()
	 * @param mixed $data The data to be signed.
	 * @param array $options Options for this method.
	 * @return array Data & signature.
	 */
	public function write($data, array $options = []) {
		$class = $options['class'];

		$futureData = $class::read(null, ['strategies' => false]);
		$futureData = [$options['key'] => $data] + $futureData;
		unset($futureData['__signature']);

		$signature = static::_signature($futureData);
		$class::write('__signature', $signature, ['strategies' => false] + $options);
		return $data;
	}

	/**
	 * Read strategy method.
	 *
	 * Validates the HMAC signature of the stored data. If the signatures match, then the data
	 * is safe and will be passed through as-is.
	 *
	 * If the stored data being read does not contain a `__signature` field, a
	 * `MissingSignatureException` is thrown. When catching this exception, you may choose
	 * to handle it by either writing out a signature (e.g. in cases where you know that no
	 * pre-existing signature may exist), or you can blackhole it as a possible tampering
	 * attempt.
	 *
	 * @throws RuntimeException On possible data tampering.
	 * @throws lithium\storage\session\strategy\MissingSignatureException On missing singature.
	 * @param array $data The data being read.
	 * @param array $options Options for this method.
	 * @return array Validated data.
	 */
	public function read($data, array $options = []) {
		if ($data === null) {
			return $data;
		}
		$class = $options['class'];

		$currentData = $class::read(null, ['strategies' => false]);

		if (!isset($currentData['__signature'])) {
			throw new MissingSignatureException('HMAC signature not found.');
		}
		if (Hash::compare($currentData['__signature'], static::_signature($currentData))) {
			return $data;
		}
		throw new RuntimeException('Possible data tampering: HMAC signature does not match data.');
	}

	/**
	 * Delete strategy method.
	 *
	 * @see lithium\storage\Session
	 * @see lithium\core\Adaptable::config()
	 * @link http://php.net/function.hash-hmac.php PHP Manual: hash_hmac()
	 * @param mixed $data The data to be signed.
	 * @param array $options Options for this method.
	 * @return array Data & signature.
	 */
	public function delete($data, array $options = []) {
		$class = $options['class'];

		$futureData = $class::read(null, ['strategies' => false]);
		unset($futureData[$options['key']]);

		$signature = static::_signature($futureData);
		$class::write('__signature', $signature, ['strategies' => false] + $options);
		return $data;
	}

	/**
	 * Calculate the HMAC signature based on the data and a secret key.
	 *
	 * @param mixed $data
	 * @param null|string $secret Secret key for HMAC signature creation.
	 * @return string HMAC signature.
	 */
	protected static function _signature($data, $secret = null) {
		unset($data['__signature']);
		$secret = ($secret) ?: static::$_secret;
		return hash_hmac('sha1', serialize($data), $secret);
	}
}

?>