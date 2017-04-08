<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

/*
 * Bootstrap.
 */
define('LITHIUM_APP_PATH', dirname(__DIR__));
define('LITHIUM_LIBRARY_PATH', dirname(__DIR__) . '/libraries');

include LITHIUM_LIBRARY_PATH . '/lithium/core/Libraries.php';

use lithium\core\Libraries;

Libraries::add('lithium');
Libraries::add('app', ['default' => true]);
Libraries::add('li3_fixtures');

if (file_exists($file = LITHIUM_LIBRARY_PATH . '/autoload.php')) {
	require_once $file;
}

/*
 * Setup test databases.
 *
 * Please note that no more than one SQLite in
 * memory database should be configured.
 */
use lithium\data\Connections;

switch (getenv('DB')) {
	case 'couchdb':
		Connections::add('test', [
			'test' => [
				'type' => 'http',
				'adapter' => 'CouchDb',
				'host' => 'localhost',
				'database' => 'lithium_test'
			]
		]);
	break;
	case 'mongodb':
		Connections::add('test', [
			'test' => [
				'type' => 'MongoDb',
				'host' => 'localhost',
				'database' => 'lithium_test'
			]
		]);
	break;
	case 'mysql':
		Connections::add('test', [
			'test' => [
				'type' => 'database',
				'adapter' => 'MySql',
				'host' => 'localhost',
				'login' => 'root',
				'password' => '',
				'database' => 'lithium_test'
			]
		]);
		Connections::add('test_alternative', [
			'test' => [
				'type' => 'database',
				'adapter' => 'MySql',
				'host' => 'localhost',
				'login' => 'root',
				'password' => '',
				'database' => 'lithium_test_alternative'
			]
		]);
	break;
	case 'pgsql':
		Connections::add('test', [
			'test' => [
				'type' => 'database',
				'adapter' => 'PostgreSql',
				'host' => 'localhost',
				'login' => 'postgres',
				'password' => '',
				'encoding' => 'UTF-8',
				'database' => 'lithium_test'
			]
		]);
		Connections::add('test_alternative', [
			'test' => [
				'type' => 'database',
				'adapter' => 'PostgreSql',
				'host' => 'localhost',
				'login' => 'postgres',
				'password' => '',
				'database' => 'lithium_test_alternative',
				'encoding' => 'UTF-8'
			]
		]);
	break;
	case 'sqlite':
		Connections::add('test', [
			'test' => [
				'type' => 'database',
				'adapter' => 'Sqlite3',
				'database' => ':memory:',
				'encoding' => 'UTF-8'
			]
		]);
	break;
}

?>