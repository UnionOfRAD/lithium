<?php
namespace lithium\data\source\http\adapter;

class CouchDb extends \lithium\data\source\Http {
	
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($config = array()) {
		$defaults = array(
			'port'       => 5984,
		);
		$config = (array)$config + $defaults;
		parent::__construct($config);
	}
}

?>