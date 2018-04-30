<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2009, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\console\command\g11n;

use Exception;
use lithium\g11n\Catalog;
use lithium\core\Libraries;

/**
 * The `Extract` class is a command for extracting messages from files.
 */
class Extract extends \lithium\console\Command {

	public $source;

	public $destination;

	public $scope;

	/**
	 * Constructor.
	 *
	 * @see lithium\console\Command
	 * @param array $config For available options, see parent class.
	 * @return void
	 */
	public function __construct(array $config = []) {
		parent::__construct($config);

		$this->source = $this->source ?: Libraries::get(true, 'path');
		$this->destination = $this->destination ?: Libraries::get(true, 'resources') . '/g11n';
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
		$this->out("Yielded {$count} item(s).");
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
	 * @return array Returns the catalog specified. Returns boolean `false` when an error occurs.
	 */
	protected function _extract() {
		$message = [];

		$message[] = 'A `Catalog` class configuration with an adapter that is capable of';
		$message[] = 'handling read requests for the `messageTemplate` category is needed';
		$message[] = 'in order to proceed. This may also be referred to as `extractor`.';
		$this->out($message);
		$this->out();

		$name = $this->_configuration([
			'adapter' => 'Code',
			'path' => $this->source,
			'scope' => $this->scope
		]);
		$configs = Catalog::config();

		try {
			return Catalog::read($name, 'messageTemplate', 'root', [
				'scope' => $configs[$name]['scope'],
				'lossy' => false
			]);
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Prompts for data source and writes template.
	 *
	 * @param array $data Data to save.
	 * @return boolean|void Return `false` if writing the catalog failed.
	 */
	protected function _writeTemplate($data) {
		$message = [];

		$message[] = 'In order to proceed you need to choose a `Catalog` configuration';
		$message[] = 'which is used for writing the template. The adapter for the configuration';
		$message[] = 'should be capable of handling write requests for the `messageTemplate`';
		$message[] = 'category.';
		$this->out($message);
		$this->out();

		$name = $this->_configuration([
			'adapter' => 'Gettext',
			'path' => $this->destination,
			'scope' => $this->scope
		]);

		if ($name != 'temporary') {
			$scope = $this->in('Scope:', ['default' => $this->scope]);
		}

		$message = [];
		$message[] = 'The template is now ready to be saved.';
		$message[] = 'Please note that an existing template will be overwritten.';
		$this->out($message);
		$this->out();

		if ($this->in('Save?', ['choices' => ['y', 'n'], 'default' => 'y']) != 'y') {
			$this->out('Aborting upon user request.');
			$this->stop(1);
		}
		try {
			return Catalog::write($name, 'messageTemplate', 'root', $data, compact('scope'));
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Helps in selecting or - if required - adding a new `Catalog` collection
	 * used for extracting or writing the template. A special configuration
	 * with the name `temporary` may be created. Should a configuration with
	 * that same name exist prior to entering this method it will be unset.
	 *
	 * @param array $options Options paired with defaults to prompt for.
	 * @return string The name of the selected or newly created configuration.
	 */
	protected function _configuration(array $options = []) {
		$configs = (array) Catalog::config();

		if (isset($configs['temporary'])) {
			unset($configs['temporary']);
		}

		if ($configs) {
			$this->out('Available `Catalog` Configurations:');
			$prompt = 'Please choose a configuration or hit enter to add a new one:';

			foreach ($configs as $name => $config) {
				$this->out(" - {$name}");
			}
		} else {
			$this->out(' - No configuration found. -');
			$prompt = 'Please hit enter to add a temporary configuration:';
		}
		$this->out();

		$name = $this->in($prompt, [
			'choices' => array_keys($configs),
			'default' => 'temporary'
		]);

		if ($name == 'temporary') {
			foreach ($options as $option => $default) {
				$configs[$name][$option] = $this->in(ucfirst($option) . ':', compact('default'));
			}
			Catalog::config($configs);
		}
		return $name;
	}
}

?>