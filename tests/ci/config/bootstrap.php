<?php

/*
 * Bootstrap.
 */
define('LITHIUM_APP_PATH', dirname(__DIR__));
define('LITHIUM_LIBRARY_PATH', dirname(LITHIUM_APP_PATH) . '/libraries');

include __DIR__ . '/libraries.php';

/*
 * Setup test databases.
 *
 * Please note that no more than one SQLite in
 * memory database should be configured.
 */
use lithium\data\Connections;

switch (getenv('DB')) {
	case 'couchdb':
		Connections::add('test', array(
			'test' => array(
				'type' => 'http',
				'adapter' => 'CouchDb',
				'host' => 'localhost',
				'database' => 'lithium_test'
			)
		));
	break;
	case 'mongodb':
		Connections::add('test', array(
			'test' => array(
				'type' => 'MongoDb',
				'host' => 'localhost',
				'database' => 'lithium_test'
			)
		));
	break;
	case 'mysql':
		Connections::add('test', array(
			'test' => array(
				'type' => 'database',
				'adapter' => 'MySql',
				'host' => 'localhost',
				'login' => 'root',
				'password' => '',
				'database' => 'lithium_test'
			)
		));
		Connections::add('test_alternative', array(
			'test' => array(
				'type' => 'database',
				'adapter' => 'MySql',
				'host' => 'localhost',
				'login' => 'root',
				'password' => '',
				'database' => 'lithium_test_alternative'
			)
		));
	break;
	case 'pgsql':
		Connections::add('test', array(
			'test' => array(
				'type' => 'database',
				'adapter' => 'PostgreSql',
				'host' => 'localhost',
				'login' => 'postgres',
				'password' => '',
				'encoding' => 'UTF-8',
				'database' => 'lithium_test'
			)
		));
		Connections::add('test_alternative', array(
			'test' => array(
				'type' => 'database',
				'adapter' => 'PostgreSql',
				'host' => 'localhost',
				'login' => 'postgres',
				'password' => '',
				'database' => 'lithium_test_alternative',
				'encoding' => 'UTF-8'
			)
		));
	break;
	case 'sqlite':
		Connections::add('test', array(
			'test' => array(
				'type' => 'database',
				'adapter' => 'Sqlite3',
				'database' => ':memory:',
				'encoding' => 'UTF-8'
			)
		));
	break;
}

?>