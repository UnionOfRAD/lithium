#!/usr/bin/env php

<?php

set_time_limit(0);

$installer = new PhpExtensions();

if (isset($argv[1]) && 'APC' === strtoupper($argv[1])) {
	$installer->install('apc');
} else {
	$installer->install('xcache');
}

$installer->install('memcached');
$installer->install('mongo');

class PhpExtensions {
	protected $extensions;
	protected $phpVersion;
	protected $iniPath;

	public function __construct() {
		$this->phpVersion = phpversion();
		$this->iniPath = php_ini_loaded_file();
		$this->extensions = array(
			'memcached' => array(
				'url' => 'http://pecl.php.net/get/memcached-1.0.2.tgz',
				'require' => array(
					// memcached 1.0.2 does not build on PHP 5.4
					'php' => array('<', '5.4')
				),
				'configure' => array(),
				'ini' => array(
					'extension=memcached.so'
				)
			),
			'apc' => array(
				'url' => 'http://pecl.php.net/get/APC-3.1.9.tgz',
				'require' => array(
					// apc 3.1.9 causes a segfault on PHP 5.4
					'php' => array('<', '5.4')
				),
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
    }

	public function install($name) {
		if (array_key_exists($name, $this->extensions)) {
			$extension = $this->extensions[$name];
			echo $name;

			if (isset($extension['require']['php'])) {
				$version = $extension['require']['php'];
				if (!version_compare($this->phpVersion, $version[1], $version[0])) {
					printf(
						" => not installed, requires a PHP version %s %s (%s installed)\n",
						$version[0],
						$version[1],
						$this->phpVersion
					);
					return;
				}
			}

			$this->system(sprintf("wget %s > /dev/null 2>&1", $extension['url']));
			$file = basename($extension['url']);
			$this->system(sprintf("tar -xzf %s > /dev/null 2>&1", $file));
			$folder = basename($file, ".tgz");
			$folder = basename($folder, ".tar.gz");
			$this->system(sprintf(
				'sh -c "cd %s && phpize && ./configure %s && make && sudo make install" > /dev/null 2>&1',
				$folder,
				implode(' ', $extension['configure'])
			));
			foreach ($extension['ini'] as $ini) {
				$this->system(sprintf("echo %s >> %s", $ini, $this->iniPath));
			}
			printf("=> installed (%s)\n", $folder);
		}
	}

	private function system($cmd) {
		$ret = 0;
		system($cmd, $ret);
		if (0 !== $ret) {
			printf("=> Command '%s' failed !", $cmd);
			exit($ret);
		}
	}
}

?>