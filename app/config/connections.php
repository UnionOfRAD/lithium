<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\data\Connections;

/**
 * Database configuration.
 * You can specify multiple configurations for production, development and testing.
 *
 * adapter => The name of a supported driver; valid options are as follows:
 *		mysql 		- MySQL 4 & 5,
 *		mysqli 		- MySQL 4 & 5 Improved Interface (PHP5 only),
 *		sqlite		- SQLite (PHP5 only),
 *		postgres	- PostgreSQL 7 and higher,
 *		mssql		- Microsoft SQL Server 2000 and higher,
 *		db2			- IBM DB2, Cloudscape, and Apache Derby (http://php.net/ibm-db2)
 *		oracle		- Oracle 8 and higher
 *		firebird	- Firebird/Interbase
 *		sybase		- Sybase ASE
 *
 * You can add custom database drivers (or override existing drivers) by adding the
 * appropriate file to app/models/datasources/dbo.  Drivers should be named 'dbo_x.php',
 * where 'x' is the name of the database.
 *
 * persistent => true / false
 * Determines whether or not the database should use a persistent connection.
 *
 * host =>
 * the host you connect to the database.  To add a socket or port number, use 'port' => #
 *
 * prefix =>
 * Uses the given prefix for all the tables in this database.  This setting can be overridden
 * on a per-table basis with the Model::$_meta['prefix'] property.
 *
 * schema =>
 * For Postgres and DB2, specifies which schema you would like to use the tables in. Postgres
 * defaults to 'public', DB2 defaults to empty.
 *
 * encoding =>
 * For MySQL, MySQLi, Postgres and DB2, specifies the character encoding to use when connecting
 * to the database.  Defaults to 'UTF-8' for DB2.  Uses database default for all others.
 */
Connections::add('default', 'Database', array(
	// 'development' => array(
		'adapter' => 'MySql',
		'host' => 'localhost',
		'login' => 'root',
		'password' => '',
		'database' => 'lithium-blog'
		// 'adapter' => 'sqlite',
		// 'database' => LITHIUM_APP_PATH . '/tmp/default.db'
	// ),
	// 'test' => array(
	// 	'adapter' => 'mysql',
	// 	'host' => 'localhost',
	// 	'login' => 'user',
	// 	'password' => 'password',
	// 	'database' => 'test_database_name'
	// ),
	// 'production' => array(
	// 	'adapter' => 'mysql',
	// 	'host' => 'localhost',
	// 	'login' => 'user',
	// 	'password' => 'password',
	// 	'database' => 'test_database_name'
	// )
));

?>