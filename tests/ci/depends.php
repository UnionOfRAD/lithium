#!/usr/bin/env php
<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2013, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

foreach (explode(' ', getenv('PHP_EXT')) ?: array() as $extension) {
	PhpExtensions::install($extension);
}

/**
 * Class to install native PHP extensions mainly
 * for preparing test runs.
 */
class PhpExtensions {
	/**
	 * Install extension by given name.
	 *
	 * Uses configration retrieved as per `php_ini_loaded_file()`.
	 *
	 * @see http://php.net/php_ini_loaded_file
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

	protected static function _opcache() {
		if (version_compare(PHP_VERSION, '5.5', '<')) {
			static::_pecl('zendopcache', '7.0.2');

			$pattern  = '/home/travis/.phpenv/versions/';
			$pattern .= phpversion() . '/lib/php/extensions/*/opcache.so';
			$files = glob($pattern);
			static::_ini(array(
				'zend_extension=' . array_pop($files)
			));
		}
		static::_ini(array(
			'opcache.enable=1',
			'opache.enable_cli=1'
		));
	}

	protected static function _apc() {
		static::_ini(array(
			'extension=apc.so',
			'apc.enabled=1',
			'apc.enable_cli=1'
		));
	}

	protected static function _memcached() {
		static::_ini(array('extension=memcached.so'));
	}

	protected static function _xcache() {
		static::_build(array(
			'url' => 'http://xcache.lighttpd.net/pub/Releases/3.1.0/xcache-3.1.0.tar.gz',
			'configure' => array('--enable-xcache'),
		));
		static::_ini(array(
			'extension=xcache.so',
			'xcache.cacher=false',
			'xcache.admin.enable_auth=0',
			'xcache.var_size=1M'
		));
	}

	protected static function _mongo() {
		static::_ini(array('extension=mongo.so'));
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

	protected static function _ini($data) {
		foreach ($data as $ini) {
			static::_system(sprintf("echo %s >> %s", $ini, php_ini_loaded_file()));
		}
	}

	protected static function _pecl($name, $forceVersion = null) {
		echo "=> installing from pecl\n";

		if ($forceVersion) {
			static::_system(sprintf('pecl install -f %s-%s', $name, $forceVersion));
		} else {
			static::_system(sprintf('pecl install %s', $name));
		}
		echo "=> installed from pecl\n";
	}

	protected static function _build($data) {
		echo "=> building\n";

		static::_system(sprintf('wget %s > /dev/null 2>&1', $data['url']));
		$file = basename($data['url']);

		static::_system(sprintf('tar -xzf %s > /dev/null 2>&1', $file));
		$folder = basename($file, '.tgz');
		$folder = basename($folder, '.tar.gz');

		$message  = 'sh -c "cd %s && phpize && ./configure %s ';
		$message .= '&& make && sudo make install" > /dev/null 2>&1';
		static::_system(sprintf(
			$message, $folder, implode(' ', $data['configure'])
		));

		echo "=> built\n";
	}
}

?>