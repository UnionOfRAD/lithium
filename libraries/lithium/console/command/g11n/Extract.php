<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\console\command\g11n;

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
		$this->destination = $this->destination ?: LITHIUM_APP_PATH . '/resources/g11n';
	}

	/**
	 * The main method of the command.
	 *
	 * @return void
	 */
	public function run() {
		$this->header('Message Extraction');

		if (!$data = $this->_extract()) {
			$this->error('Yielded no items.');
			return 1;
		}
		$count = count($data);
		$this->out("Yielded {$count} items.");
		$this->out();

		$this->header('Message Template Creation');

		if (!$this->_writeTemplate($data)) {
			$this->error('Failed to write template.');
			return 1;
		}
		$this->out();

		return 0;
	}

	/**
	 * Extracts translatable strings from multiple files.
	 *
	 * @param array $files Absolute paths to files.
	 * @return array
	 */
	protected function _extract() {
		$message[] = 'A `Catalog` class configuration with an adapter that is capable of';
		$message[] = 'handling read requests for the `messageTemplate` category is needed';
		$message[] = 'in order to proceed. This may also be referred to as `extractor`.';
		$this->out($message);
		$this->out();

		$configs = (array) Catalog::config();

		$this->out('Available `Catalog` Configurations:');
		foreach ($configs as $name => $config) {
			$this->out(" - {$name}");
		}
		$this->out();

		$name = $this->in('Please choose a configuration or hit [enter] to add one:', array(
			'choices' => array_keys($configs)
		));

		if (!$name) {
			$adapter = $this->in('Adapter:', array(
				'default' => 'Code'
			));
			$path = $this->in('Path:', array(
				'default' => $this->source
			));
			$scope = $this->in('Scope:', array(
				'default' => $this->scope
			));
			$name =	'runtime' . uniqid();
			$configs[$name] = compact('adapter', 'path', 'scope');
		}
		Catalog::config($configs);

		return Catalog::read('messageTemplate', 'root', compact('name') + array(
			'scope' => $configs[$name]['scope'],
			'lossy' => false
		));
	}

	/**
	 * Prompts for data source and writes template.
	 *
	 * @param array $data Data to save.
	 * @return void
	 */
	protected function _writeTemplate($data) {
		$message[] = 'In order to proceed you need to choose a `Catalog` configuration';
		$message[] = 'which is used for writing the template. The adapter for the configuration';
		$message[] = 'should be capable of handling write requests for the `messageTemplate`';
		$message[] = 'category.';
		$this->out($message);
		$this->out();

		$configs = (array) Catalog::config();

		$this->out('Available `Catalog` Configurations:');
		foreach ($configs as $name => $config) {
			$this->out(" - {$name}");
		}
		$this->out();

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
			Catalog::config($configs);
		}
		$scope = $configs[$name]['scope'] ?: $this->in('Scope:', array('default' => $this->scope));

		$message = array();
		$message[] = 'The template is now ready to be saved.';
		$message[] = 'Please note that an existing template will be overwritten.';
		$this->out($message);
		$this->out();

		if ($this->in('Save?', array('choices' => array('y', 'n'), 'default' => 'n')) != 'y') {
			$this->out('Aborting upon user request.');
			$this->stop(1);
		}
		return Catalog::write('messageTemplate', 'root', $data, compact('name', 'scope'));
	}
}

?>