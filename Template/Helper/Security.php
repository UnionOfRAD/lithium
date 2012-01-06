<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Template\Helper;

/**
 * The `Security` helper is responsible for various tasks associated with verifying the authenticity
 * of requests, including embedding secure tokens to protect against CSRF attacks.
 *
 * @see Lithium\Security\Validation\RequestToken
 */
class Security extends \Lithium\Template\Helper {

	protected $_classes = array(
		'requestToken' => 'Lithium\Security\Validation\RequestToken'
	);

	/**
	 * Configures the helper with the default settings for interacting with security tokens.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = array()) {
		$defaults = array('sessionKey' => 'security.token', 'salt' => null);
		parent::__construct($config + $defaults);
	}

	/**
	 * Generates a request key used to protect your forms against CSRF attacks. See the
	 * `RequestToken` class for examples and proper usage.
	 *
	 * @see Lithium\Security\Validation\RequestToken
	 * @param array $options Options used as HTML when generating the field.
	 * @return string Returns a hidden `<input />` field containing a request-specific CSRF token
	 *         key.
	 */
	public function requestToken(array $options = array()) {
		$defaults = array('name' => 'security.token', 'id' => false);
		$options += $defaults;
		$requestToken = $this->_classes['requestToken'];

		$flags = array_intersect_key($this->_config, array('sessionKey' => '', 'salt' => ''));
		$value = $requestToken::key($flags);

		$name = $options['name'];
		unset($options['name']);
		return $this->_context->form->hidden($name, compact('value') + $options);
	}
}

?>