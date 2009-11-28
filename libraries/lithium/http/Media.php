<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\http;

use \Exception;
use \lithium\util\String;
use \lithium\core\Libraries;

/**
 * The `Media` class facilitates content-type mapping (mapping between content-types and file
 * extensions), handling static assets and globally configuring how the framework handles output in
 * different formats.
 *
 * Using the `Media` class, you can globally configure input and output of different types of
 * content, i.e.:
 * {{{ embed:lithium\tests\cases\http\MediaTest::testCustomEncodeHandler(4-13) }}}
 *
 * You may then render CSV content from anywhere in your application. For example, in a controller
 * you may do the following:
 *
 * {{{	$this->render(array('csv', 'data' => Post::find('all')));}}}
 */
class Media extends \lithium\core\StaticObject {

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
		'zip'          => 'application/x-zip'
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
			'view'   => false,
			'layout' => false,
			'encode' => 'json_encode',
			'decode' => 'json_decode'
		),
		'text' => array(
			'view'     => false,
			'layout'   => false,
			'template' => false
		),
		'form' => array(
			'view'   => false,
			'layout' => false,
			'encode' => 'http_build_query'
		)
	);

	/**
	 * Contains default path settings for various asset types. For each type, the corresponding
	 * array key maps to the general type name, i.e. `'js'` or `'image'`. Each type contains a set
	 * of keys which define their locations and default behavior. For more information how each key
	 * works, see `Media::assets()`.
	 *
	 * @var array
	 * @see lithium\http\Media::assets()
	 */
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
	 * Examples:
	 * {{{// Get a list of all available media types:
	 * Media::types(); // returns array('ai', 'amf', 'atom', ...);
	 * }}}
	 *
	 * {{{// Add a custom media type:
	 * Media::type('my', 'text/x-my', array('view' => '\my\custom\View', 'layout' => false));
	 * }}}
	 *
	 * {{{// Remove a custom media type:
	 * Media::type('my', false);
	 * }}}
	 *
	 * @param string $type A file extension for the type, i.e. `'txt'`, `'js'`, or `'atom'`.
	 * @param mixed $content Optional. A string or array containing the content-type(s) that
	 *              `$type` should map to.  If `$type` is an array of content-types, the first one
	 *              listed should be the "primary" type.
	 * @param array $options Optional.  The handling options for this media type. Possible keys are:
	 *              - `'view'`: Specifies the view class to use when rendering this content.
	 *              - `'template'`: Specifies a `String::insert()`-style path to use when
	 *                searching for template files.
	 *              - `'layout'`: Specifies a `String::insert()`-style path to use when searching
	 *                for layout files.
	 *              - `'encode'`: A (string) function name or (object) closure that handles
	 *                encoding or serializing content into this format.
	 *              - `'decode'`: A (string) function name or (object) closure that handles
	 *                decoding or unserializing content from this format.
	 * @return mixed If `$content` and `$options` are empty, returns an array with `'content'` and
	 *               `'options'` keys, where `'content'` is the content-type(s) that correspond to
	 *               `$type` (can be a string or array, if multiple content-types are available),
	 *               and `'options'` is the array of options which define how this content-type
	 *               should be handled.  If `$content` or `$options` are non-empty, returns `null`.
	 * @see lithium\http\Media::$_types
	 * @see lithium\http\Media::$_handlers
	 * @see lithium\util\String::insert()
	 */
	public static function type($type, $content = null, $options = array()) {
		$defaults = array(
			'view' => false,
			'template' => false,
			'layout' => false,
			'encode' => false,
			'decode' => false
		);

		if ($content === false) {
			unset(static::$_types[$type], static::$_handlers[$type]);
		}

		if (empty($content) && empty($options)) {
			$content = isset(static::$_types[$type]) ? static::$_types[$type] : null;
			$options = isset(static::$_handlers[$type]) ? static::$_handlers[$type] : null;
			return compact('content', 'options');
		}

		if (!empty($content)) {
			static::$_types[$type] = $content;
		}
		if (!empty($options)) {
			static::$_handlers[$type] = ((array)$options + $defaults);
		}
	}

	/**
	 * Gets or sets options for various asset types.
	 *
	 * @param string $type The name of the asset type, i.e. `'js'` or `'css'`.
	 * @param array $options If registering a new asset type or modifying an existing asset type,
	 *              contains settings for the asset type, where the available keys are as follows:
	 *              - `'suffix'`: The standard suffix for this content type, with leading dot ('.')
	 *                if applicable.
	 *              - `'filter'`: An array of key/value pairs representing simple string
	 *                replacements to be done on a path once it is generated.
	 *              - `'path'`: An array of key/value pairs where the keys are
	 *                `String::insert()`-compatible paths, and the values are array lists of keys
	 *                to be inserted into the path string.
	 * @return array If `$type` is empty, an associative array of all registered types and all
	 *               associated options is returned. If `$type` is a string and `$options` is empty,
	 *               returns an associative array with the options for `$type`. If `$type` and
	 *               `$options` are both non-empty, returns `null`.
	 * @see lithium\util\String::insert()
	 */
	public static function assets($type = null, $options = array()) {
		$defaults = array('suffix' => null, 'filter' => null, 'path' => array());

		if (empty($type)) {
			return static::$_assets;
		}
		if ($options === false) {
			unset(static::$_assets[$type]);
		}
		if (empty($options)) {
			return isset(static::$_assets[$type]) ? static::$_assets[$type] : null;
		}
		$options = (array)$options + $defaults;

		if (isset(static::$_assets[$type])) {
			static::$_assets[$type] = array_filter((array)$options) + static::$_assets[$type];
		} else {
			static::$_assets[$type] = $options;
		}
	}

	/**
	 * Calculates the web-accessible path to a static asset, usually a JavaScript, CSS or image
	 * file.
	 *
	 * @param string $path The path to the asset, relative to the given `$type`s path and without a
	 *               suffix. If the path contains a URI Scheme (eg. `http://`), no path munging will
	 *               occur.
	 * @param string $type The asset type. See `Media::$_assets`.
	 * @param array $options Contains setting for finding and handling the path, where the keys are
	 *              the following:
	 *              - `'base'`: The base URL of your application. Defaults to `null` for no base
	 *                path. This is usually set with the return value of a call to `env('base')` on
	 *                an instance of `lithium\action\Request`.
	 *              - `'timestamp'`: Appends the last modified time of the file to the path if
	 *                `true`. Defaults to `false`.
	 *              - `'filter'`: An array of key/value pairs representing simple string
	 *                replacements to be done on a path once it is generated.
	 *              - `'path'`: An array of paths to search for the asset in. The paths should use
	 *              `String::insert()` formatting. See `Media::$_assets` for more.
	 *              - `suffix`: The suffix to attach to the path, generally a file extension.
	 *              - `check`: Check for the existence of the file before returning. Defaults to
	 *              `false`.
	 * @return string Returns the publicly-accessible absolute path to the static asset. If checking
	 *         for the asset's existence (`$options['check']`), returns `false` if it does not exist
	 *         in your `/webroot` directory, or the `/webroot` directories of one of your included
	 *         plugins.
	 * @see lithium\http\Media::$_assets
	 * @see lithium\action\Request::env()
	 */
	public static function asset($path, $type, $options = array()) {
		$defaults = array(
			'base' => null,
			'timestamp' => false,
			'filter' => null,
			'path' => array(),
			'suffix' => null,
			'check' => false,
			'library' => 'app'
		);
		$options += (static::$_assets[$type] + $defaults);
		$type = isset(static::$_assets[$type]) ? $type : 'generic';
		$params = compact('path', 'type', 'options');

		return static::_filter(__METHOD__, $params, function($self, $params, $chain) {
			extract($params);

			if (preg_match('/^[a-z0-9-]+:\/\//i', $path)) {
				return $path;
			}

			$library = isset($options['plugin']) ? $options['plugin'] : $options['library'];
			$config = Libraries::get($library);
			$paths = $options['path'];

			($library == 'app') ? end($paths) : reset($paths);
			$options['library'] = basename($config['path']);
			unset($options['plugin']);

			if ($options['suffix'] && strpos($path, $options['suffix']) === false) {
				$path .= $options['suffix'];
			}
			$file = $config['path'] . '/webroot';

			if ($path[0] == '/') {
				$result = "{$options['base']}{$path}";
				$file .= $path;
			} else {
				$result = String::insert(key($paths), compact('path') + $options);
				$realPath = str_replace('{:library}/', '', key($paths));
				$file = String::insert($realPath, array('base' => $file) + compact('path'));
			}
			$path = $result;

			if ($qOffset = strpos($file, '?')) {
				$file = substr($file, 0, $qOffset);
			}

			if ($options['check'] && !is_file($file)) {
				return false;
			}

			if (is_array($options['filter']) && !empty($options['filter'])) {
				$keys = array_keys($options['filter']);
				$values = array_values($options['filter']);
				$path = str_replace($keys, $values, $path);
			}

			if ($options['timestamp'] && is_file($file)) {
				$separator = (strpos($path, '?') !== false) ? '&' : '?';
				$path .= $separator . filemtime($file);
			}
			return $path;
		});
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
		$defaults = array('encode' => null, 'template' => null, 'layout' => null, 'view' => null);

		$options += array('type' => $response->type());
		$type = $options['type'];
		$result = null;

		if (!isset(static::$_types[$type])) {
			throw new Exception("Unhandled media type '$type'");
		}

		if (isset(static::$_handlers[$type])) {
			$h = (array)static::$_handlers[$type] + (array)static::$_handlers['default'];
		} else {
			$h = $options + $defaults;
			$filter = function($v) { return $v !== null; };
			$h = array_filter($h, $filter) + static::$_handlers['default'] + $defaults;
		}
		$response->body(static::_handle($h, $data, $options));
		$response->headers('Content-type', current((array)static::$_types[$type]));
	}

	/**
	 * For media types registered in `$_handlers` which include an `'encode'` setting, encodes data
	 * according to the specified media type.
	 *
	 * @param string $type Specifies the media type into which `$data` will be encoded. This media
	 *        type must have an `'encode'` setting specified in `Media::$_handlers`.
	 * @param mixed $data Arbitrary data you wish to encode. Note that some encoders can only handle
	 *              arrays or objects.
	 * @param array $options Handler-specific options.
	 * @return mixed
	 */
	public static function encode($type, $data, $options = array()) {
		if (!isset(static::$_handlers[$type])) {
			return null;
		}
		$method = static::$_handlers[$type]['encode'];
		return is_string($method) ? $method($data) : $method($data, $handler + $options);
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
			$options += (array) $options['request']->params;
			$handler['request'] = $options['request'];
		}

		switch (true) {
			case $handler['encode']:
				$method = $handler['encode'];
				$result = is_string($method) ? $method($data) : $method($data, $handler);
			break;
			case $handler['view']:
				$view = new $handler['view']($handler);
				$result = $view->render('all', $data, $options);
			break;
			case ($handler['template'] === false) && is_string($data):
				$result = $data;
			break;
			default:
				$result = print_r($data, true);
			break;
		}
		return $result;
	}
}

?>