<?php

namespace lithium\console\commands;

use \Exception;
use \DateTime;
use \lithium\g11n\Catalog;
use \lithium\util\String;

/**
 * The `G11n` class is a command for extracting messages from files.
 */
class G11n extends \lithium\console\Command {

	/**
	 * The main method of the commad.
	 *
	 * @return void
	 */
	public function run() {
		$this->out('G11n Command');
		$this->hr();

		$sourcePath = LITHIUM_APP_PATH;
		$destinationPath = LITHIUM_APP_PATH . '/resources/po/';

		$this->out('Extracting messages from source code.');
		$this->hr();
		$timeStart = microtime(true);

		$data = $this->_extract($sourcePath);
		$this->nl();
		$this->out(String::insert('Yielded {:countItems} items taking {:duration} seconds.', array(
			'countItems' => count($data['root']),
			'duration' => round(microtime(true) - $timeStart, 4)
		)));

		$this->nl();
		$this->out('Additional data.');
		$this->hr();

		$meta = $this->_meta();

		$this->nl();
		$this->out('Messages template.');
		$this->hr();

		$message  = 'Would you like to save the template now? ';
		$message .= '(An existing template will be overwritten)';

		if ($this->in($message, array('choices' => array('y', 'n'), 'default' => 'n')) != 'y') {
			$this->stop(1, 'Aborting upon user request.');
		}
		$this->nl();

		$this->_writeTemplate($data, $meta);

		$this->nl();
		$this->out('Done.');
	}

	/**
	 * Extracts translatable strings from multiple files.
	 *
	 * @param array $files Absolute paths to files
	 * @return array
	 */
	protected function _extract($path) {
		Catalog::config(array(
			'extract' => array('adapter' => 'Code', 'path' => $path)
		));
		return Catalog::read('message.template', 'root', array('name' => 'extract'));
	}

	/**
	 * Prompts for addtional data.
	 *
	 * @return array
	 */
	protected function _meta() {
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
		$configs = Catalog::config()->to('array');
		$name = $this->in('Please choose a config:', array(
			'choices' => array_keys($configs),
			'default' => 'extract'
		));

		Catalog::write('message.template', 'root', compact('name'));
	}
}

?>