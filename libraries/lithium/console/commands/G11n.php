<?php

namespace lithium\console\commands;

use \Exception;
use \DateTime;
use \lithium\g11n\Catalog;

class G11n extends \lithium\console\Command {

	public function main() {
		Console::out('G11n Script');
		Console::hr('=', 2);

		$sourcePath = LITHIUM_APP_PATH;
		$destinationPath = LITHIUM_APP_PATH . '/locales/po/';

		Console::out('Extracting messages from source code.');
		Console::hr('-', 2);
		$timeStart = microtime(true);

		$data = $this->_extract($sourcePath);

		Console::nl();
		Console::out('Yielded {:countItems} items taking {:duration} seconds.', array(
			'countItems' => count($data),
			'duration' => round(microtime(true) - $timeStart, 4)
		));

		Console::nl();
		Console::out('Additional data.');
		Console::hr('-', 2);

		$meta = $this->_meta();

		Console::nl();
		Console::out('Messages template.');
		Console::hr('-', 2);

		$message  = 'Would you like to save the template now? ';
		$message .= '(An existing template will be overwritten)';
		if (Console::in($message, 'n', 'y/n') != 'y') {
			Console::stop(1, 'Aborting upon user request.');
		}
		Console::nl();

		$this->_writeTemplate($data, $meta);

		Console::nl();
		Console::out('Done.');
	}

	/**
	 * Extracts translatable strings from multiple files.
	 *
	 * @param array $files Absolute paths to files
	 * @return array
	 */
	function _extract($path) {
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
			'package' => Console::in('Package name:', null, 'app'),
			'packageVersion' => Console::in('Package version:'),
			'copyrightYear' => Console::in('Copyright year:', null, $now->format('Y')),
			'copyright' => Console::in('Copyright holder:'),
			'copyrightEmail' => Console::in('Copyright email address:'),
			'templateCreationDate' => $now->format('Y-m-d H:iO'),
		);
	}

	/**
	 * Prompts for data source and writes template.
	 *
	 * @param array $data Data to save
	 * @param array $meta Additional data to save
	 * @return void
	 * @todo readd meta data
	 */
	protected function _writeTemplate($data, $meta) {
		$configs = array_keys(Catalog::config());

		foreach ($configs as $key => $config) {
			Console::out($key);
		}
		$key = Console::in('Please choose a config:');
		$name = $configs[$key];

		Catalog::write('message.template', 'root', compact('name'));
	}
}

?>