<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\analysis\logger\adapter;

use lithium\net\http\Media;

/**
 * The `FirePhp` logger allows you to log messages to the FirePHP console.
 *
 * {{{
 * use lithium\analysis\Logger;
 *
 * Logger::config(array(
 * 	'default' => array('adapter' => 'FirePhp')
 * ));
 * Logger::debug('Something happened!');
 * Logger::error('This is not good!');
 * }}}
 *
 * This will cause the message and other debug settings added to the header of the
 * response, where FirePHP is able to locate and print it accordingly. As this adapter
 * implements the protocol specification directly, you don't need another vendor library to
 * use it.
 *
 * Because FirePHP is not a conventional logging destination like a file or a database, you can
 * pass everything (except resources) to the logger and inspect it further in FirePHP. In fact,
 * every message that is passed will be encoded via `json_encode()`, so check out this built
 * in method for more information on how your message will look like in the FirePHP console.
 *
 * {{{
 * Logger::debug(array('debug' => 'me));
 * Logger::debug(new \lithium\action\Response());
 * }}}
 *
 * @see http://www.firephp.org/
 * @see http://www.firephp.org/Wiki/Reference/Protocol
 * @see http://php.net/manual/en/function.json-encode.php
 */
class FirePhp extends \lithium\core\Object {

	/**
	 * These headers are specified by FirePHP and get added as headers to the response.
	 *
	 * @var array
	 */
	protected $_headers = array(
		'X-Wf-Protocol-1' => 'http://meta.wildfirehq.org/Protocol/JsonStream/0.2',
		'X-Wf-1-Plugin-1' => 'http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.3',
		'X-Wf-1-Structure-1' => 'http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1'
	);

	/**
	 * This is a mapping table that maps Lithium log levels to FirePHP log levels as they
	 * do not correlate directly and FirePHP only accepts a distinct set.
	 *
	 * @var array
	 */
	protected $_levels = array(
		'emergency' => 'ERROR',
		'alert'		=> 'ERROR',
		'critical' 	=> 'ERROR',
		'error' 	=> 'ERROR',
		'warning' 	=> 'WARN',
		'notice' 	=> 'INFO',
		'info' 		=> 'INFO',
		'debug' 	=> 'LOG'
	);

	/**
	 * This self-incrementing counter allows the user to log more than one message per request.
	 *
	 * @var integer
	 */
	protected $_counter = 1;

	/**
	 * Appends the message to the response header for FirePHP.
	 *
	 * @param string $type
	 * @param string $message
	 * @return void
	 */
	public function write($type, $message) {
		$headers = $this->_headers;
		$message = $this->_message($type, $message);
		$log = compact('headers', 'message');

		Media::applyFilter('render', function($self, $params, $chain) use ($log) {
			extract($log);
			$response = $params['response'];

			$response->headers += $headers;
			$response->headers[$message['key']] = $message['content'];

			$params['response'] = $response;
			return $chain->next($self, $params, $chain);
		});
	}

	/**
	 * Generates a string representation of the type and message, suitable for FirePHP.
	 *
	 * @param string $type
	 * @param string $message
	 * @return string The string representation of the type and message.
	 */
	protected function _message($type, $message) {
		$key = 'X-Wf-1-1-1-'.$this->_counter++;

		$content = array(
			array('Type' => $this->_levels[$type]),
			$message
		);
		$content = json_encode($content);
		$content = strlen($content).'|'.$content.'|';

		return compact('key', 'content');
	}

}

?>