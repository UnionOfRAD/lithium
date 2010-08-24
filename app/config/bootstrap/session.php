<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

/**
 * This configures your session storage. The Cookie storage adapter must be connected first, since
 * it intercepts any writes where the `'expires'` key is set in the options array.
 */
use lithium\storage\Session;

Session::config(array(
	'cookie' => array('adapter' => 'Cookie'),
	'default' => array('adapter' => 'Php')
));

/**
 * Uncomment this to enable forms-based authentication. The configuration below will attempt to
 * authenticate users against a `User` model. In a controller, run
 * `Auth::check('default', $this->request)` to authenticate a user. This will check
 *
 * @see lithium\security\auth\adapter\Form
 */
use lithium\security\Auth;

Auth::config(array(
	'default' => array(
		'adapter' => 'Form',
		'model' => 'User',
		'fields' => array('username', 'password')
	)
));

?>