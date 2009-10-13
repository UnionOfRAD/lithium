<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\http;

use \Exception;
use \lithium\util\String;

class Media extends \lithium\core\Object {

	/**
	 * Maps file extensions to content-types.  Used to set response types and determine request
	 * types.  Can be modified with Media::type().
	 *
	 * @var array
	 * @see lithium\http\Media::type()
	 */
	protected static $_types = array(
		'ai'           => 'application/postscript',
		'amf'          => 'application/x-amf',
		'atom'         => 'application/atom+xml',
		'bin'          => 'application/octet-stream',
		'bz2'          => 'application/x-bzip',
		'class'        => 'application/octet-stream',
		'css'          => 'text/css',
		'csv'          => array('application/csv', 'application/vnd.ms-excel'),
		'file'         => 'multipart/form-data',
		'form'         => 'application/x-www-form-urlencoded',
		'htm'          => array('alias' => 'html'),
		'html'         => array('text/html', '*/*'),
		'js'           => 'text/javascript',
		'json'         => 'application/json',
		'pdf'          => 'application/pdf',
		'rss'          => 'application/rss+xml',
		'swf'          => 'application/x-shockwave-flash',
		'tar'          => 'application/x-tar',
		'text'         => 'text/plain',
		'txt'          => array('alias' => 'text'),
		'vcf'          => 'text/x-vcard',
		'xhtml'        => array('application/xhtml+xml', 'application/xhtml', 'text/xhtml'),
		'xhtml-mobile' => 'application/vnd.wap.xhtml+xml',
		'xml'          => array('application/xml', 'text/xml'),
		'zip'          => 'application/x-zip',
	);

	/**
	 * A map of media handler objects or callbacks, mapped to media types.
	 *
	 * @var array
	 */
	protected static $_handlers = array(
		'default' => array(
			'view'     => '\lithium\template\View',
			'template' => '{:library}/views/{:controller}/{:template}.{:type}.php',
			'layout'   => '{:library}/views/layouts/{:layout}.{:type}.php',
			'encode'   => false,
			'decode'   => false
		),
		'html' => array(),
		'json' => array(
			'view' => false,
			'layout' => false,
			'encode' => 'json_encode',
			'decode' => 'json_decode'
		),
		'text' => null
	);

	protected static $_assets = array(
		'js' => array('suffix' => '.js', 'filter' => null, 'path' => array(
			'{:base}/{:library}/js/{:path}' => array('base', 'library', 'path'),
			'{:base}/js/{:path}' => array('base', 'path')
		)),
		'css' => array('suffix' => '.css', 'filter' => null, 'path' => array(
			'{:base}/{:library}/css/{:path}' => array('base', 'library', 'path'),
			'{:base}/css/{:path}' => array('base', 'path')
		)),
		'image' => array('suffix' => null, 'filter' => null, 'path' => array(
			'{:base}/{:library}/img/{:path}' => array('base', 'library', 'path'),
			'{:base}/img/{:path}' => array('base', 'path')
		)),
		'generic' => array('suffix' => null, 'filter' => null, 'path' => array(
			'{:base}/{:library}/{:path}' => array('base', 'library', 'path'),
			'{:base}/{:path}' => array('base', 'path')
		))
	);

	/**
	 * Returns the list of registered media types.  New types can be set with the `type()` method.
	 *
	 * @return array Returns an array of media type extensions or short-names, which comprise the
	 *               list of types handled.
	 */
	public static function types() {
		return array_keys(static::$_types);
	}

	/**
	 * Map an extension to a particular content-type (or types) with a set of options.
	 *
	 * @param string $type A file extension for the type, i.e. 'txt', 'js', or 'atom'
	 * @param mixed $content Optional.  A string or array containing the content-type(s) that
	 *                    $type should map to.  If $type is an array of content-types, the first
	 *                    one listed should be the "primary" type.
	 * @param array $options Optional.  The handling options for this media type.
	 * @return mixed If $content and $options are empty, returns an array with 'content' and
	 *               'options' keys, where 'content' is the content-type(s) that correspond to
	 *               $type (can be a string or array, if multiple content-types are available), and
	 *               'options' is the array of options which define how this content-type should be
	 *               handled.  If $content or $options are non-empty, returns null.
	 * @see lithium\http\Media::$_types
	 * @see lithium\http\Media::$_handlers
	 */
	public static function type($type, $content = null, $options = array()) {
		if (empty($content) && empty($options)) {
			return array(
				'content' => (isset(static::$_types[$type]) ? static::$_types[$type] : null),
				'options' => (isset(static::$_handlers[$type]) ? static::$_handlers[$type] : null)
			);
		}
		if (!empty($content)) {
			static::$_types[$type] = $content;
		}
		if (!empty($options)) {
			static::$_handlers[$type] = $options;
		}
	}

	/**
	 * Gets or sets options for various asset types.
	 *
	 * @param string $type 
	 * @param string $options 
	 * @return void
	 */
	public static function assets($type = null, $options = array()) {
		if (empty($type)) {
			return static::$_assets;
		}
		if (empty($options)) {
			return isset(static::$_assets[$type]) ? static::$_assets[$type] : null;
		}

		if (isset(static::$_assets[$type])) {
			static::$_assets[$type] = $options + static::$_assets[$type];
		} else {
			static::$_assets[$type] = $options;
		}
	}

	/**
	 * Calculates the web-accessible path to a static asset, usually a JavaScript, CSS or image
	 * file.
	 *
	 * @param string $path 
	 * @param string $type 
	 * @param array $options 
	 * @return string
	 */
	public static function asset($path, $type, $options = array()) {
		if (preg_match('/^[a-z0-9-]+:\/\//i', $path)) {
			return $path;
		}
		$type = isset(static::$_assets[$type]) ? $type : 'generic';

		$defaults = array(
			'base' => null, 'timestamp' => false, 'filter' => null, 
			'path' => array(), 'suffix' => null
		);
		$options += (static::$_assets[$type] + $defaults);

		if ($path[0] !== '/') {
			end($options['path']);
			$path = String::insert(rtrim(key($options['path']), '/'), compact('path') + $options);
		}

		if (strpos($path, '?') === false) {
			if ($options['suffix'] && strpos($path, $options['suffix']) === false) {
				$path .= $options['suffix'];
			}
			if ($options['timestamp']) {
				$path .= '?' . @filemtime(WWW_ROOT . $path);
			}
		}

		if (is_array($options['filter']) && $options['filter']) {
			$path = str_replace($options['filter'][0], $options['filter'][1], $path);
		}
		return $path;
	}

	/**
	 * Renders data (usually the result of a controller action) and generates a string
	 * representation of it, based on the type of expected output.
	 *
	 * @param object $response A reference to a Response object into which the operation will be
	 *               rendered. The content of the render operation will be assigned to the `$body`
	 *               property of the object, and the `'Content-type'` header will be set
	 *               accordingly.
	 * @param mixed $data
	 * @param array $options
	 * @return void
	 * @todo Implement proper exception handling
	 */
	public static function render(&$response, $data = null, $options = array()) {
		$defaults = array(
			'encode' => function($data) { return print_r($data, true); },
			'template' => null,
			'layout' => null,
			'view' => null
		);
		$options += array('type' => $response->type());
		$type = $options['type'];
		$result = null;

		if (!array_key_exists($type, static::$_types)) {
			throw new Exception("Unhandled type '$type'");
		}

		$h = array_key_exists($type, static::$_handlers) ? static::$_handlers[$type] : null;
		$h = is_null($h) ? $defaults : $h + static::$_handlers['default'] + $defaults;

		$response->body(static::_handle($h, $data, $options));
		$response->headers('Content-type', current((array)static::$_types[$type]));
	}

	/**
	 * Called by `Media::render()` to render response content. Given a content handler and data,
	 * calls the content handler and passes in the data, receiving back a rendered content string.
	 *
	 * @param array $handler 
	 * @param array $data 
	 * @param array $options 
	 * @return string
	 */
	protected static function _handle($handler, $data, $options) {
		$result = '';

		if (isset($options['request'])) {
			$options += (array)$options['request']->params;
			$handler['request'] = $options['request'];
		}

		switch (true) {
			case $handler['view']:
				$view = new $handler['view']($handler);
				$result = $view->render('all', $data, $options);
			break;
			case $handler['encode']:
				$method = $handler['encode'];
				$result = is_string($method) ? $method($data) : $method($data, $handler);
			break;
			default:
				
			break;
		}
		return $result;
	}
}

?>