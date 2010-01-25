<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command;

use \Phar;
use \RuntimeException;
use \lithium\core\Libraries;

/**
 * The Library command is used to archive and extract Phar::GZ archives. Requires zlib extension.
 * In addition, communicate with the a given server to add plugins and extensions to the
 * current application. Push archived plugins to the server.
 *
 */
class Library extends \lithium\console\Command {

	/**
	 * Absolute path to config file.
	 *
	 * @var string
	 */
	public $conf = null;

	/**
	 * Server host to query for plugins.
	 *
	 * @var string
	 */
	public $server = 'lab.lithify.me';

	/**
	 * The port for the server.
	 *
	 * @var string
	 */
	public $port = 80;

	/**
	 * The username for the server authentication.
	 *
	 * @var string
	 */
	public $username = '';

	/**
	 * The password for corresponding username.
	 *
	 * @var string
	 */
	public $password = '';

	/**
	 * @see `force`
	 * @var boolean
	 */
	public $f = false;

	/**
	 * Force operation to complete. Typically used for overwriting files.
	 *
	 * @var string
	 */
	public $force = false;

	/**
	 * Filter used for including files in archive.
	 *
	 * @var string
	 */
	public $filter = '/\.(php|htaccess|jpg|png|gif|css|js|ico|json|ini)$/';

	/**
	 * Holds settings from conf file
	 *
	 * @var array
	 */
	protected $_settings = array();

	/**
	 * some classes
	 *
	 * @package default
	 */
	protected $_classes = array(
		'service' => '\lithium\http\Service',
		'response' => '\lithium\console\Response'
	);

	/**
	 * Initialize _settings from `--conf`
	 *
	 * @return void
	 */
	protected function _init() {
		parent::_init();
		$this->_settings['servers'][$this->server] = true;
		if (file_exists($this->conf)) {
			$this->_settings += json_decode($this->conf, true);
		}
		$this->force = $this->f ? $this->f : $this->force;
	}

	/**
	 * Add configuration and write data in json format.
	 *
	 * @param string $key (server)
	 * @param string $value value of key
	 * @param string $options [optional]
	 * @return void
	 */
	public function config($key = null, $value = null, $options = true) {
		if (empty($key) || empty($value)) {
			return false;
		}
		switch($key) {
			case 'server':
				$this->_settings['servers'][$value] = $options;
			break;
		}
		return file_put_contents($this->conf, json_encode($this->_settings));
	}

	/**
	 * Extract an archive into a path. If one param exists, the app.phar.gz template will be used.
	 * If both parameters exist, then the first will be the template archive and the second will be
	 * the name of the extracted archive
	 *
	 * `li3 library extract myapp` : uses the command/create/template/app.phar.gz
	 * `li3 library extract another_archive myapp`
	 * `li3 library extract plugin li3_plugin` : uses the command/create/template/plugin.phar.gz
	 *
	 * @param string $name if only param, command/create/template/app.phar.gz extracted to $name
	 *     otherwise, the template name or full path to extract `from` phar.gz.
	 * @param string $result if exists $name is extracted to $result
	 * @return boolean
	 */
	public function extract($name = 'new', $result = null) {
		$from = 'app';
		$to = $name;

		if ($result) {
			$from = $name;
			$to = $result;
		}
		$to = $this->_toPath($to);

		if ($from[0] !== '/') {
			$from = Libraries::locate('command.create.template', $from, array(
				'filter' => false, 'type' => 'file', 'suffix' => '.phar.gz',
			));
			if (!$from || is_array($from)) {
				return false;
			}
		}
		if (file_exists($from)) {
			$archive = new Phar($from);
			if ($archive->extractTo($to)) {
				$this->out(basename($to) . " created in " . dirname($to) . " from {$from}");
				return true;
			}
		}
		$this->error("Could not extract {$to} from {$from}");
		return false;
	}

	/**
	 * Create the Phar::GZ archive from a given directory. If no params, the current working
	 * directory is archived with the name of that directory. If one param, the current working
	 * directory will be archive with the name provided. If both params, the first is the
	 * name or path to the library to archive and the second is the name of the resulting archive
	 *
	 * `li3 library archive my_archive` : archives current working directory to my_archive.phar.gz
	 * `li3 library archive myapp my_archive` : archives 'myapp' to 'my_archive.phar.gz'
	 *
	 * @param string $name if only param, the archive name for the current working directory
	 *     otherwise, The library name or path to the directory to compress.
	 * @param string $result if exists, The name of the resulting archive
	 * @return boolean
	 */
	public function archive($name = null, $result = null) {
		if (ini_get('phar.readonly') == '1') {
			throw new RuntimeException('set phar.readonly = 0 in php.ini');
		}
		$from = $name;
		$to = $name;

		if ($result) {
			$from = $name;
			$to = $result;
		}
		$path = $this->_toPath($to);

		if (file_exists("{$path}.phar")) {
			if (!$this->force) {
				$this->error(basename($path) . ".phar already exists in " . dirname($path));
				return false;
			}
			Phar::unlinkArchive("{$path}.phar");
		}
 		$archive = new Phar("{$path}.phar");
		$from = $this->_toPath($from);
		$result = (boolean) $archive->buildFromDirectory($from, $this->filter);

		if (file_exists("{$path}.phar.gz")) {
			if (!$this->force) {
				$this->error(basename($path) . ".phar.gz already exists in " . dirname($path));
				return false;
			}
			Phar::unlinkArchive("{$path}.phar.gz");
		}
		if ($result) {
			$archive->compress(Phar::GZ);
			$this->out(basename($path) . ".phar.gz created in " . dirname($path) . " from {$from}");
			return true;
		}
		$this->error("Could not create archive from {$from}");
		return false;
	}


	/**
	 * List all the plugins and extensions available on the server.
	 *
	 * @param string $type plugins|extensions
	 * @return void
	 */
	public function find($type = 'plugins') {
		$results = array();

		foreach (array_keys($this->_settings['servers']) as $server) {
			$service = new $this->_classes['service'](array('host' => $server));
			$results[$server] = json_decode($service->get("lab/{$type}"));

			if (empty($results[$server])) {
				$this->out("No {$type} at {$server}");
				continue;
			}
			foreach ((array) $results[$server] as $data) {
				$header = "{$server} > {$data->name}";

				if (!$header) {
					$header = "{$server} > {$data->class}";
				}
				$out = array(
					"{$data->summary}",
					"Version: {$data->version}", "Created: {$data->created}",
				);

				$this->header($header);
				$this->out(array_filter($out));
			}
		}
	}

	/**
	 * Install plugins or extensions to the current application.
	 * For plugins, the install commands specified in the formula is run.
	 *
	 * @param string $plugin name of plugin to add
	 * @return boolean
	 */
	public function install($plugin = null) {
		$results = array();
		foreach ($this->_settings['servers'] as $server) {
			$service = new $this->_classes['service'](array('host' => $server));
			$results[$server] = json_decode($service->get("lab/{$plugin}.json"));
		}
		if (count($results) == 1) {
			$plugin = current($results);
		}
		$this->header($plugin->name);

		$isGit = (
			isset($plugin->sources->git) &&
			strpos(shell_exec('git --version'), 'git version 1.6') !== false
		);
		if ($isGit) {
			$result = shell_exec("cd {$this->path} && git clone {$plugin->sources->git}");
			return $result;
		}
		if (isset($plugin->sources->phar)) {
			$remote = $plugin->sources->phar;
			$local = $this->path . '/' . basename($plugin->sources->phar);
			$write = file_put_contents($local, file_get_contents($remote));
			$archive = new Phar($local);
			return $archive->extractTo(
				$this->path . '/' . basename($plugin->sources->phar, '.phar.gz')
			);
		}
		return false;
	}

	/**
	 * Create a formula for the given library name
	 *
	 * @param string $name the library name or full path to the plugin
	 * @return boolean
	 */
	public function formulate($name = null) {
		if (!$name) {
			$name = $this->in("please supply a name");
		}
		$result = false;
		$path = $this->_toPath($name);
		$name = basename($name);
		$formula = "{$path}/config/{$name}.json";

		if (file_exists($path) && !file_exists($formula)) {
			$data = json_encode(array(
				'name' => $name, 'version' => null,
				'summary' => null,
				'maintainers' => array(array(
					'name' => '', 'email' => '', 'website' => ''
				)),
				'sources' => array("http://{$this->server}/lab/download/{$name}.phar.gz"),
				'commands' => array(
					'install' => array(), 'update' => array(), 'remove' => array(),
				),
				'requires' => array()
			));
			$result = file_put_contents($formula, $data);
		}
		if ($result) {
			$this->out("Formula for {$name} created in {$path}.");
			return true;
		}
		$this->error("Formula for {$name} not created in {$path}");
		return true;
	}

	/**
	 * Send a plugin archive to the server. The plugin must have a formula.
	 *
	 * @param string $name the library name or full path to the archive to send
	 * @return void
	 */
	public function push($name = null) {
		if (!$name) {
			$name = $this->in("please supply a name");
		}
		$path = $this->_toPath($name);
		$name = basename($name);
		$file = "{$path}.phar.gz";

		if (file_exists($file)) {
			$service = new $this->_classes['service'](array(
				'host' => $this->server, 'port' => $this->port,
				'login' => $this->username, 'password' => $this->password
			));
			$boundary = md5(date('r', time()));
			$headers = array("Content-Type: multipart/form-data; boundary={$boundary}");
			$name = basename($file);
			$data = join("\r\n", array(
				"--{$boundary}",
				"Content-Disposition: form-data; name=\"phar\"; filename=\"{$name}\"",
				"Content-Type: application/phar", "",
				base64_encode(file_get_contents($file)),
				"--{$boundary}--"
			));
			$result = json_decode($service->post('/lab/server/receive', $data, compact('headers')));
			if ($service->last->response->status['code'] == 201) {
				$this->out(array(
					"{$result->name} added to {$this->server}.",
					"See http://{$this->server}/lab/plugins/view/{$result->id}"
				));
				return $result;
			}
			if (!empty($result->error)) {
				$this->out($result->error);
			}
			return false;
		}
		$this->error("{$file} does not exist. Run `li3 library archive {$name}`");
		return false;
	}

	/**
	 * Update installed plugins. For plugins, runs update commands specified in Formula.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function update() {
		$this->error('Please implement me');
	}

	/**
	 * Take a name and return the path.
	 *
	 * @param string $name
	 * @return string
	 */
	protected function _toPath($name) {
		if ($name[0] === '/') {
			return $name;
		}
		$library = Libraries::get($name);

		if (!empty($library['path'])) {
			return $library['path'];
		}
		$path = $this->request->env('working');
		return ($name) ? "{$path}/{$name}" : $path;
	}
}

?>