#!/usr/bin/env php
<?php

$installer = new PhpExtensions();

if (isset($argv[1]) && 'APC' === strtoupper($argv[1])) {
	$installer->install('apc');
} else {
	$installer->install('xcache');
}
$installer->install('mongo');

class PhpExtensions {
	/**
	 * Holds build, configure and install instructions for PHP extensions.
	 *
	 * @var array Extensions to build keyed by extension identifier.
	 */
	protected $_extensions = array(
		'memcached' => array(
			'url' => 'http://pecl.php.net/get/memcached-2.0.1.tgz',
			'require' => array(),
			'configure' => array(),
			'ini' => array(
				'extension=memcached.so'
			)
		),
		'apc' => array(
			'url' => 'http://pecl.php.net/get/APC-3.1.10.tgz',
			'require' => array(),
			'configure' => array(),
			'ini' => array(
				'extension=apc.so',
				'apc.enabled=1',
				'apc.enable_cli=1'
			)
		),
		'xcache' => array(
			'url' => 'http://xcache.lighttpd.net/pub/Releases/1.3.2/xcache-1.3.2.tar.gz',
			'require' => array(
				'php' => array('<', '5.4')
			),
			'configure' => array('--enable-xcache'),
			'ini' => array(
				'extension=xcache.so',
				'xcache.cacher=false',
				'xcache.admin.enable_auth=0',
				'xcache.var_size=1M'
			)
		),
		'mongo' => array(
			'url' => 'http://pecl.php.net/get/mongo-1.2.7.tgz',
			'require' => array(),
			'configure' => array(),
			'ini' => array(
				'extension=mongo.so'
			)
		)
	);

	/**
	 * After instantiation holds the current PHP version.
	 *
	 * @see http://php.net/phpversion
	 * @var string Current PHP version.
	 */
	protected $_phpVersion;

	/**
	 * After instantiation holds the path to loaded PHP configuration file.
	 *
	 * @see http://php.net/php_ini_loaded_file
	 * @var string Path to loaded PHP configuration file i.e. `/usr/local/php/php.ini`.
	 */
	protected $_iniPath;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->_phpVersion = phpversion();
		$this->_iniPath = php_ini_loaded_file();
    }

	public function install($name) {
		if (array_key_exists($name, $this->_extensions)) {
			$extension = $this->_extensions[$name];
			echo $name;

			if (isset($extension['require']['php'])) {
				$version = $extension['require']['php'];
				if (!version_compare($this->_phpVersion, $version[1], $version[0])) {
					$message = " => not installed, requires a PHP version %s %s (%s installed)\n";
					printf($message, $version[0], $version[1], $this->_phpVersion);
					return;
				}
			}

			$this->_system(sprintf('wget %s > /dev/null 2>&1', $extension['url']));
			$file = basename($extension['url']);

			$this->_system(sprintf('tar -xzf %s > /dev/null 2>&1', $file));
			$folder = basename($file, '.tgz');
			$folder = basename($folder, '.tar.gz');

			$message  = 'sh -c "cd %s && phpize && ./configure %s ';
			$message .= '&& make && sudo make install" > /dev/null 2>&1';
			$this->_system(sprintf($message, $folder, implode(' ', $extension['configure'])));

			foreach ($extension['ini'] as $ini) {
				$this->_system(sprintf("echo %s >> %s", $ini, $this->_iniPath));
			}
			printf("=> installed (%s)\n", $folder);
		}
	}

	protected function _system($command) {
		$return = 0;
		system($command, $return);

		if (0 !== $return) {
			printf("=> Command '%s' failed !", $command);
			exit($return);
		}
	}
}

?>