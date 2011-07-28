<?php
namespace lithium\security\auth\acl\db;
//use \lithium\util\String;
//use lithium\security\Password;

class Acos extends lithium\security\auth\acl\Acl {

	protected $_meta = array('source' => 'acos');

/**
 * Binds to ACOs nodes through permissions settings
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Acos' => array('with' => 'Permission'));
}
?>