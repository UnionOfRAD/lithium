<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\strategy;

use \Exception;
use \RuntimeException;

/**
 * HMAC strategy.
 */
class Hmac extends \lithium\core\Object {

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
	public function __construct(array $config = array()) {
		if (!isset($config['secret'])) {
			throw new Exception("HMAC strategy requires a secret key.");
		}
		static::$_secret = $config['secret'];
	}

	/**
	 * Write strategy method.
	 * Adds an HMAC signature to the data. Note that this will transform the
	 * passed `$data` to an array, and add a `__signature` key with the HMAC-caculated
	 * value.
	 *
	 * @see lithium\storage\Session
	 * @see lithium\core\Adaptable::config()
	 * @see http://php.net/manual/en/function.hash-hmac.php
	 * @param mixed $data The data to be signed.
	 * @param array $options Options for this method.
	 * @return array Data & signature.
	 */
	public function write($data, array $options = array()) {
		$class = $options['class'];

		$futureData = $class::read(null, array('strategies' => false));
		$futureData += array($options['key'] => $data);
		unset($futureData['__signature']);

		$signature = static::_signature($futureData);
		$class::write('__signature', $signature, array('strategies' => false) + $options);
		return $data;
	}

	/**
	 * Read strategy method.
	 * Validates the HMAC signature of the stored data. If the signatures match, then
	 * the data is safe, and the 'valid' key in the returned data will be
	 *
	 * @param array $data the Data being read.
	 * @param array $options Options for this method.
	 * @return array validated data
	 */
	public function read($data, array $options = array()) {
		$class = $options['class'];

		$futureData = $class::read(null, array('strategies' => false));
		unset($futureData['__signature']);

		if (!isset($futureData['__signature'])) {
			$signature = hash_hmac('sha1', serialize($futureData), static::$_secret);
			$class::write('__signature', $signature, array('strategies' => false) + $options);
			return $data;
		}

		$currentSignature = $futureData['__signature'];
		$signature = static::_signature($futureData);

		if ($signature !== $currentSignature) {
			$message = "Possible data tampering - HMAC signature does not match data.";
			throw new RuntimeException($message);
		}
		return $data;
	}

	/**
	 * Delete strategy method.
	 *
	 * @see lithium\storage\Session
	 * @see lithium\core\Adaptable::config()
	 * @see http://php.net/manual/en/function.hash-hmac.php
	 * @param mixed $data The data to be signed.
	 * @param array $options Options for this method.
	 * @return array Data & signature.
	 */
	public function delete($data, array $options = array()) {
		$class = $options['class'];

		$futureData = $class::read(null, array('strategies' => false));
		unset($futureData[$options['key']], $futureData['__signature']);

		$signature = static::_signature($futureData);
		$class::write('__signature', $signature, array('strategies' => false) + $options);
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
		$secret = ($secret) ?: static::$_secret;
		return hash_hmac('sha1', serialize($data), $secret);
	}
}

?>