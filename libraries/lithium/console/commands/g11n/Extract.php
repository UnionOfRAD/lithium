<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\commands\g11n;

use \Exception;
use \DateTime;
use \lithium\g11n\Catalog;

/**
 * The `Extract` class is a command for extracting messages from files.
 */
class Extract extends \lithium\console\Command {

	public $source;

	public $destination;

	public $scope;

	public function _init() {
		parent::_init();
		$this->source = $this->source ?: LITHIUM_APP_PATH;
		$this->destination = $this->destination ?: LITHIUM_APP_PATH . '/extensions/g11n/data';
	}

	/**
	 * The main method of the command.
	 *
	 * @return void
	 */
	public function run() {
		$this->header('Message Extraction');

		if (!$data = $this->_extract()) {
			$this->err('Yielded no items.');
			return 1;
		}
		$count = count($data['root']);
		$this->out("Yielded {$count} items.");
		$this->nl();

		$this->header('Message Template Creation');

		$meta = $this->_meta();
		$this->nl();

		if (!$this->_writeTemplate($data, $meta)) {
			$this->err('Failed to write template.');
			return 1;
		}
		$this->nl();

		return 0;
	}

	/**
	 * Extracts translatable strings from multiple files.
	 *
	 * @param array $files Absolute paths to files
	 * @return array
	 */
	protected function _extract() {
		$message[] = 'A `Catalog` class configuration with an adapter that is capable of';
		$message[] = 'handling read requests for the `message.template` category is needed';
		$message[] = 'in order to proceed.';
		$this->out($message);
		$this->nl();

		$configs = (array)Catalog::config()->to('array');

		$this->out('Available `Catalog` Configurations:');
		foreach ($configs as $name => $config) {
			$this->out(" - {$name}");
		}
		$this->nl();

		$name = $this->in('Please choose a configuration or hit [enter] to add one:', array(
			'choices' => array_keys($configs)
		));

		if (!$name) {
			$adapter = $this->in('Adapter:', array(
				'default' => 'Gettext'
			));
			$path = $this->in('Path:', array(
				'default' => $this->destination
			));
			$scope = $this->in('Scope:', array(
				'default' => $this->scope
			));
			$name =	'runtime' . uniqid();
			$configs[$name] = compact('adapter', 'path', 'scope');
		}
		Catalog::config($configs);
		$scope = $configs[$name]['scope'];
		return Catalog::read('message.template', 'root', compact('name', 'scope'));
	}

	/**
	 * Prompts for addtional data.
	 *
	 * @return array
	 */
	protected function _meta() {
		$message[] = 'Please provide some data which is used when creating the';
		$message[] = 'template.';
		$this->out($message);
		$this->nl();

		$now = new DateTime();
		return array(
			'package' => $this->in('Package name:', array('default' => 'app')),
			'packageVersion' => $this->in('Package version:'),
			'copyrightYear' => $this->in('Copyright year:', array('default' => $now->format('Y'))),
			'copyright' => $this->in('Copyright holder:'),
			'copyrightEmail' => $this->in('Copyright email address:'),
			'templateCreationDate' => $now->format('Y-m-d H:iO'),
		);
	}

	/**
	 * Prompts for data source and writes template.
	 *
	 * @param array $data Data to save
	 * @param array $meta Additional data to save
	 * @return void
	 */
	protected function _writeTemplate($data, $meta) {
		$message[] = 'In order to proceed you need to choose a `Catalog` configuration';
		$message[] = 'which is used for writing the template. The adapter for the configuration';
		$message[] = 'should be capable of handling write requests for the `message.template`';
		$message[] = 'category.';
		$this->out($message);
		$this->nl();

		$configs = (array)Catalog::config()->to('array');

		$this->out('Available `Catalog` Configurations:');
		foreach ($configs as $name => $config) {
			$this->out(" - {$name}");
		}
		$this->nl();

		$name = $this->in('Please choose a configuration or hit [enter] to add one:', array(
			'choices' => array_keys($configs)
		));

		if (!$name) {
			$adapter = $this->in('Adapter:', array(
				'default' => 'Gettext'
			));
			$path = $this->in('Path:', array(
				'default' => $this->destination
			));
			$scope = $this->in('Scope:', array(
				'default' => $this->scope
			));
			$name =	'runtime' . uniqid();
			$config[$name] = compact('adapter', 'path', 'scope');
			Catalog::config($config);
		}

		$configs = Catalog::config()->to('array');
		$scope = $configs[$name]['scope'];

		$message = array();
		$message[] = 'The template is now ready to be saved.';
		$message[] = 'Please note that an existing template will be overwritten.';
		$this->out($message);
		$this->nl();

		if ($this->in('Save?', array('choices' => array('y', 'n'), 'default' => 'n')) != 'y') {
			$this->out('Aborting upon user request.');
			$this->stop(1);
		}
		return Catalog::write('message.template', $data, compact('name', 'scope'));
	}
}

?>