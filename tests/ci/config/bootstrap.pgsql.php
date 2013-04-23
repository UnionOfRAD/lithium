<?php
define('LITHIUM_APP_PATH', dirname(__DIR__));
define('LITHIUM_LIBRARY_PATH', dirname(LITHIUM_APP_PATH) . '/libraries');

include __DIR__ . '/libraries.php';

use lithium\data\Connections;

/**
 * Setup test database
 */
Connections::add('test', array(
	'test' => array(
		'type' => 'database',
		'adapter' => 'PostgreSql',
		'host' => 'localhost',
		'login' => 'postgres',
		'password' => '',
		'database' => 'lithium_test',
		'encoding' => 'UTF-8'
	)
));

?>