<?php
namespace lithium\security\auth\acl\db;
//use \lithium\util\String;
//use lithium\security\Password;

class Permissions extends lithium\security\auth\acl\Acl {
	
/**
 * Override default `_meta` options
 *
 * @var array
 * @access protected
 */
	protected $_meta = array('source' => 'aros_acos');

/**
 * Permissions link AROs with ACOs
 *
 * @var array
 * @access public
 */
	public $belongsTo = array('Aros', 'Acos');
}
?>