<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\security\auth\adapter;

/**
 * The `Http` adapter provides basic and digest authentication based on the HTTP protocol.
 * By default, the adapter uses Http Digest based authentication.
 * {{{
 * Auth::config(array('name' => array('adapter' => 'Http', 'users' => array('gwoo' => 'li3'))))
 * }}}
 *
 * To use Basic authentication, set the `method` to basic.
 * {{{
 * Auth::config(array('name' => array(
 *     'adapter' => 'Http', 'users' => array('gwoo' => 'li3'),
 *     'method' => 'basic'
 * )))
 * }}}
 *
 * @link http://tools.ietf.org/html/rfc2068#section-14.8
 * @see `\lithium\action\Request`
 */
class Http extends \lithium\core\Object {

	/**
	 * Setup default configuration options.
	 *
	 * @param array $config
	 *        - `method`: default: `digest` options: `basic|digest`
	 *        - `realm`: default: `Protected by Lithium`
	 *        - `users`: the users to permit. key => value pair of username => password
	 */
	public function __construct(array $config = array()) {
		$defaults = array(
			'method' => 'digest', 'realm' => basename(LITHIUM_APP_PATH), 'users' => array()
		);
		parent::__construct($config + $defaults);
	}

	/**
	 * Called by the `Auth` class to run an authentication check against the HTTP data using the
	 * credientials in a data container (a `Request` object), and returns an array of user
	 * information on success, or `false` on failure.
	 *
	 * @param object $request A env container which wraps the authentication credentials used
	 *               by HTTP (usually a `Request` object). See the documentation for this
	 *               class for further details.
	 * @param array $options Additional configuration options. Not currently implemented in this
	 *              adapter.
	 * @return array Returns an array containing user information on success, or `false` on failure.
	 */
	public function check($request, array $options = array()) {
		$method = "_{$this->_config['method']}";
		return $this->{$method}($request);
	}

	/**
	 * A pass-through method called by `Auth`. Returns the value of `$data`, which is written to
	 * a user's session. When implementing a custom adapter, this method may be used to modify or
	 * reject data before it is written to the session.
	 *
	 * @param array $data User data to be written to the session.
	 * @param array $options Adapter-specific options. Not implemented in the `Form` adapter.
	 * @return array Returns the value of `$data`.
	 */
	public function set($data, array $options = array()) {
		return $data;
	}

	/**
	 * Called by `Auth` when a user session is terminated. Not implemented in the `Form` adapter.
	 *
	 * @param array $options Adapter-specific options. Not implemented in the `Form` adapter.
	 * @return void
	 */
	public function clear(array $options = array()) {
	}

	/**
	 * Handler for HTTP Basic Authentication
	 *
	 * @param string $request a `\lithium\action\Request` object
	 * @return void
	 */
	protected function _basic($request) {
		$users = $this->_config['users'];
		$username = $request->env('PHP_AUTH_USER');
		$password = $request->env('PHP_AUTH_PW');

		if (!isset($users[$username]) || $users[$username] !== $password) {
			$this->_writeHeader("WWW-Authenticate: Basic realm=\"{$this->_config['realm']}\"");
			return;
		}
		return compact('username', 'password');
	}

	/**
	 * Handler for HTTP Digest Authentication
	 *
	 * @param string $request a `\lithium\action\Request` object
	 * @return void
	 */
	protected function _digest($request) {
		$realm = $this->_config['realm'];
		$data = array(
			'username' => null, 'nonce' => null, 'nc' => null,
			'cnonce' => null, 'qop' => null, 'uri' => null,
			'response' => null
		);

		$result = array_map(function ($string) use (&$data) {
			$parts = explode('=', trim($string), 2) + array('', '');
			$data[$parts[0]] = trim($parts[1], '"');
		}, explode(',', $request->env('PHP_AUTH_DIGEST')));

		$users = $this->_config['users'];
		$password = !empty($users[$data['username']]) ? $users[$data['username']] : null;

		$user = md5("{$data['username']}:{$realm}:{$password}");
		$nonce = "{$data['nonce']}:{$data['nc']}:{$data['cnonce']}:{$data['qop']}";
		$req = md5($request->env('REQUEST_METHOD') . ':' . $data['uri']);
		$hash = md5("{$user}:{$nonce}:{$req}");

		if (!$data['username'] || $hash !== $data['response']) {
			$nonce = uniqid();
			$opaque = md5($realm);

			$message = "WWW-Authenticate: Digest realm=\"{$realm}\",qop=\"auth\",";
			$message .= "nonce=\"{$nonce}\",opaque=\"{$opaque}\"";
			$this->_writeHeader($message);
			return;
		}
		return array('username' => $data['username'], 'password' => $password);
	}

	/**
	 * Helper method for writing headers. Mainly used to override the output while testing.
	 *
	 * @param string $string the string the send as a header
	 * @return void
	 */
	protected function _writeHeader($string) {
		header($string, true);
	}
}

?>