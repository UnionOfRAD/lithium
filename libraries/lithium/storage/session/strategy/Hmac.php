<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\storage\session\strategy;

/**
 * HMAC strategy.
 */
class Hmac extends \lithium\core\Object {


	public function __construct(array $config = array()) {
		parent::__construct($config + array('secret' => 'my_secret_key'));
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
	 * @return array Data & signature.
	 */
	public static function write($data) {
		if (!isset($this->_config['secret'])) {
			return $data;
		}
		$secret = $this->_config['secret'];
		$signature = hash_hmac('sha1', serialize($data), $secret);
		$data = array('data' => $data, '__signature' => $signature);
	}

	/**
	 * Read strategy method.
	 * Validates the HMAC signature of the stored data. If the signatures match, then
	 * the data is safe, and the 'valid' key in the returned data will be
	 *
	 * @param array $data the Data being read.
	 * @return array validated data
	 */
	public static function read($data) {
		if (!isset($this->_config['secret']) || !isset($data['signature'])) {
			return $data;
		}
		$secret = $this->_config['secret'];
		$signature = hash_hmac('sha1', serialize($data), $secret);
		$data['__valid'] = ($signature === $secret);
		unset($data['__signature']);
		return $data;
	}
}

?>