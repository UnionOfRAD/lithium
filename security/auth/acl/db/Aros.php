<?php
namespace lithium\security\auth\acl\db;
//use \lithium\util\String;
//use lithium\security\Password;

class Aros extends lithium\security\auth\acl\Acl {

	protected $_meta = array('source' => 'aros');

/**
 * Binds to AROs nodes through permissions settings
 *
 * @var array
 * @access public
 */
	public $hasAndBelongsToMany = array('Aro' => array('with' => 'Permission'));
}
?>