#!/usr/bin/env php
<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2012, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

// use \RuntimeException;

foreach (explode(' ', getenv('PHP_EXT')) ?: [] as $extension) {
	PhpExtensions::install($extension);
}
foreach (explode(' ', getenv('COMPOSER_PKG')) ?: [] as $package) {
	ComposerPackages::install($package);
}

/**
 * Class to install native PHP extensions mainly for preparing test runs
 * in continuous integration environments like Travis CI.
 */
class PhpExtensions {

	/**
	 * Install extension by given name.
	 *
	 * @param string $name The name of the extension to install.
	 * @return void
	 */
	public static function install($name) {
		if (!method_exists('PhpExtensions', $method = "_{$name}")) {
			return;
		}
		printf("=> installing (%s)\n", $name);
		static::$method();
		printf("=> installed (%s)\n", $name);
	}

	protected static function _redis() {
		static::_ini([
			'extension=redis.so'
		]);
	}

	protected static function _opcache() {
		static::_ini([
			'opcache.enable=1',
			'opcache.enable_cli=1'
		]);
	}

	protected static function _apcu() {
		if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
			static::_pecl('apcu', '5.1.8', true);
		} else {
			static::_pecl('apcu', '4.0.11', true);
		}
		static::_ini([
			'apc.enabled=1',
			'apc.enable_cli=1'
		]);
	}

	protected static function _memcached() {
		static::_ini(['extension=memcached.so']);
	}

	protected static function _mongo() {
		static::_ini(['extension=mongo.so']);
	}

	protected static function _mongodb() {
		static::_pecl('mongodb');
		static::_ini(['extension=mongodb.so']);
	}

	/**
	 * Executes given command, reports and exits in case it fails.
	 *
	 * @param string $command The command to execute.
	 * @return void
	 */
	protected static function _system($command) {
		$return = 0;
		system($command, $return);

		if (0 !== $return) {
			printf("=> Command '%s' failed !", $command);
			exit($return);
		}
	}

	/**
	 * Add INI settings. Uses configration retrieved as per `php_ini_loaded_file()`.
	 *
	 * @link http://php.net/php_ini_loaded_file
	 * @param array $data INI settings to add.
	 * @return void
	 */
	protected static function _ini(array $data) {
		foreach ($data as $ini) {
			static::_system(sprintf("echo %s >> %s", $ini, php_ini_loaded_file()));
		}
	}

	/**
	 * Installs a package from pecl.
	 *
	 * @param string $name The name of the package to install.
	 * @param string|null $forceVersion Optionally a specific version string, if not provided
	 *                    will install the latest available package version..
	 * @param boolean $autoAccept
	 * @return void
	 */
	protected static function _pecl($name, $forceVersion = null, $autoAccept = false) {
		echo "=> installing from pecl\n";

		if ($forceVersion) {
			$command = sprintf('pecl install -f %s-%s', $name, $forceVersion);
		} else {
			$command = sprintf('pecl install %s', $name);
		}
		if ($autoAccept) {
			$command = 'printf "\n" | ' . $command;
		}

		static::_system($command);
		echo "=> installed from pecl\n";
	}

	/**
	 * Builds a given item from source, by first retrieving
	 * the source tarball then phpize'ing and making it.
	 *
	 * @param array $data An array with information about remote source location and special
	 *              arguments that should be passed to `configure`.
	 * @return void
	 */
	protected static function _build(array $data) {
		echo "=> building\n";

		static::_system(sprintf('wget %s > /dev/null 2>&1', $data['url']));
		$file = basename($data['url']);

		static::_system(sprintf('tar -xzf %s > /dev/null 2>&1', $file));
		$folder = basename($file, '.tgz');
		$folder = basename($folder, '.tar.gz');

		$message  = 'sh -c "cd %s && phpize && ./configure %s ';
		$message .= '&& make && make install" > /dev/null 2>&1';
		static::_system(sprintf(
			$message, $folder, implode(' ', $data['configure'])
		));

		echo "=> built\n";
	}
}

/**
 * Allows to install composer packages into the test environment.
 */
class ComposerPackages {

	public static function install($package) {
		$return = 0;
		system($command = "composer require {$package}", $return);

		if (0 !== $return) {
			printf("=> Command '%s' failed !", $command);
			exit($return);
		}
	}
}

?>