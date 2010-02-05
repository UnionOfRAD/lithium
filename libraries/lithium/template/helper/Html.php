<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\template\helper;

/**
 * A template helper that assists in generating HTML content. Accessible in templates via
 * `$this->html`, which will auto-load this helper into the rendering context. For examples of how
 * to use this helper, see the documentation for a specific method. For a list of the
 * template strings this helper uses, see the `$_strings` property.
 */
class Html extends \lithium\template\Helper {

	/**
	 * String templates used by this helper.
	 *
	 * @var array
	 */
	protected $_strings = array(
		'block'            => '<div{:options}>{:content}</div>',
		'block-end'        => '</div>',
		'block-start'      => '<div{:options}>',
		'charset'     => '<meta http-equiv="Content-Type" content="{:type}; charset={:charset}" />',
		'doctype'          => '<!DOCTYPE {:version} PUBLIC "{:dtd}" "{:url}">',
		'image'            => '<img src="{:path}"{:options} />',
		'js-block'         => '<script type="text/javascript"{:options}>{:content}</script>',
		'js-end'           => '</script>',
		'js-start'         => '<script type="text/javascript"{:options}>',
		'link'             => '<a href="{:url}"{:options}>{:title}</a>',
		'list'             => '<ul{:options}>{:content}</ul>',
		'list-item'        => '<li{:options}>{:content}</li>',
		'meta'             => '<meta{:options}/>',
		'meta-link'        => '<link href="{:url}"{:options} />',
		'para'             => '<p{:options}>{:content}</p>',
		'para-start'       => '<p{:options}>',
		'script'           => '<script type="text/javascript" src="{:path}"{:options}></script>',
		'style'            => '<style type="text/css"{:options}>{:content}</style>',
		'style-import'     => '<style type="text/css"{:options}>@import url({:url});</style>',
		'style-link'       => '<link rel="{:type}" type="text/css" href="{:path}"{:options} />',
		'table-header'     => '<th{:options}>{:content}</th>',
		'table-header-row' => '<tr{:options}>{:content}</tr>',
		'table-cell'       => '<td{:options}>{:content}</td>',
		'table-row'        => '<tr{:options}>{:content}</tr>',
		'tag'              => '<{:name}{:options}>{:content}</{:name}>',
		'tag-end'          => '</{:name}>',
		'tag-start'        => '<{:name}{:options}>'
	);

	/**
	 * Document type definitions
	 *
	 * @var	array
	 */
	protected $_docTypes = array(
		'html4-strict' => array(
			'version' => 'HTML',
			'dtd' => '-//W3C//DTD HTML 4.01//EN',
			'url' => 'http://www.w3.org/TR/html4/strict.dtd'
		),
		'html4-trans'  => array(
			'version' => 'HTML',
			'dtd' => '-//W3C//DTD HTML 4.01 Transitional//EN',
			'url' => 'http://www.w3.org/TR/html4/loose.dtd'
		),
		'html4-frame'  => array(
			'version' => 'HTML',
			'dtd' => '-//W3C//DTD HTML 4.01 Frameset//EN',
			'url' => 'http://www.w3.org/TR/html4/frameset.dtd'
		),
		'xhtml-strict' => array(
			'version' => 'html',
			'dtd' => '-//W3C//DTD XHTML 1.0 Strict//EN',
			'url' => 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'
		),
		'xhtml-trans' => array(
			'version' => 'html',
			'dtd' => '-//W3C//DTD XHTML 1.0 Transitional//EN',
			'url' => 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'
		),
		'xhtml-frame' => array(
			'version' => 'html',
			'dtd' => '-//W3C//DTD XHTML 1.0 Frameset//EN',
			'url' => 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd'
		),
		'xhtml11' => array(
			'version' => 'html',
			'dtd' => '-//W3C//DTD XHTML 1.1//EN',
			'url' => 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd'
		)
	);

	/**
	 * Data used for custom <meta /> links.
	 *
	 * @var array
	 */
	protected $_metaLinks = array(
		'atom' => array('type' => 'application/atom+xml', 'rel' => 'alternate'),
		'rss'  => array('type'  => 'application/rss+xml', 'rel' => 'alternate'),
		'icon' => array('type' => 'image/x-icon', 'rel' => 'icon')
	);

	/**
	 * Used by output handlers to calculate asset paths in conjunction with the `Media` class.
	 *
	 * @var array
	 * @see lithium\net\http\Media
	 */
	public $contentMap = array(
		'script' => 'js',
		'style'  => 'css',
		'image' => 'image',
		'_metaLink' => 'generic'
	);

	/**
	 * Returns a doctype string.
	 *
	 * Possible doctypes:
	 *
	 * - `html4-strict`: HTML4 Strict.
	 * - `html4-trans`: HTML4 Transitional.
	 * - `html4-frame`: HTML4 Frameset.
	 * - `xhtml-strict`: XHTML1 Strict.
	 * - `xhtml-trans`: XHTML1 Transitional.
	 * - `xhtml-frame`: XHTML1 Frameset.
	 * - `xhtml11`: XHTML1.1
	 *
	 * Note that the HTML5 doctype has been omitted, because the doctype tag is simply
	 * `<!doctype html>`.
	 *
	 * @param string $type Doctype to use.
	 * @return string An HTML doctype tag.
	 */
	public function docType($type = 'xhtml-trans') {
		if (isset($this->_docTypes[$type])) {
			return $this->_render(__METHOD__, 'doctype', $this->_docTypes[$type]);
		}
	}

	/**
	 * Returns a charset meta-tag.
	 *
	 * @param string $charset The character set to be used in the meta tag. Example: `"utf-8"`.
	 * @return string A meta tag containing the specified character set.
	 */
	public function charset($charset = null) {
		$options = array('type' => 'text/html');
		$options['charset'] = $charset ?: 'utf-8';
		return $this->_render(__METHOD__, 'charset', $options);
	}

	/**
	 * Creates an HTML link (`<a />`) or a document meta-link (`<link />`).
	 *
	 * If `$url` starts with `"http://"` or `"https://"`, this is treated as an external link.
	 * Otherwise, it is treated as a path to controller/action and parsed using
	 * the `Router::match()` method (where `Router` is the routing class dependency specified by
	 * the rendering context, i.e. `lithium\template\view\Renderer::$_classes`).
	 *
	 * If `$url` is empty, `$title` is used in its place.
	 *
	 * @param string $title The content to be wrapped by an `<a />` tag.
	 * @param mixed $url Can be a string representing a URL relative to the base of your Lithium
	 *              applcation, an external URL (starts with `'http://'` or `'https://'`), an anchor
	 *              name starting with `'#'` (i.e. `'#top'`), or an array defining a set of request
	 *              parameters that should be matched against a route in `Router`.
	 * @param array $options Array of HTML attributes.
	 * @return string Returns an `<a />` or `<link />` element.
	 */
	public function link($title, $url = null, $options = array()) {
		$defaults = array('escape' => true);
		$options += $defaults;

		if (isset($options['type']) && $type = $options['type']) {
			unset($options['type']);
			$options = array_diff_key($options, $defaults) + compact('title');
			return $this->_metaLink($type, $url, $options);
		}

		$url = is_null($url) ? $title : $url;
		$params = $options;
		$options = array_diff_key($options, $defaults);
		return $this->_render(__METHOD__, 'link', compact('title', 'url', 'options'), $params);
	}

	/**
	 * Returns a JavaScript include tag (`<script />` element). If the filename is prefixed with
	 * `"/"`, the path will be relative to the base path of your application.  Otherwise, the path
	 * will be relative to your JavaScript path, usually `webroot/js`.
	 *
	 * @param mixed $path String path to JavaScript file, or an array of paths.
	 * @param array $options
	 * @return string
	 */
	public function script($path, $options = array()) {
		$defaults = array('inline' => true);
		$options += $defaults;
		$m = __METHOD__;

		if (is_array($path)) {
			$result = join("\n\t", array_map(array(&$this, __FUNCTION__), $path));
			return ($options['inline']) ? $result . "\n" : null;
		}
		$params = compact('path') + array('options' => array_diff_key($options, $defaults));

		$script = $this->_filter(__METHOD__, $params, function($self, $params, $chain) use ($m) {
			return $self->invokeMethod('_render', array($m, 'script', $params));
		});

		if ($options['inline']) {
			return $script;
		}
		if ($this->_context) {
			$this->_context->scripts($script);
		}
	}

	/**
	 * Creates a link element for CSS stylesheets.
	 *
	 * @param mixed $path The name of a CSS style sheet in `/app/webroot/css`, or an array
	 *              containing names of CSS stylesheets in that directory.
	 * @param array $options Array of HTML attributes.
	 * @return string CSS <link /> or <style /> tag, depending on the type of link.
	 * @filter This method can be filtered.
	 */
	public function style($path, $options = array()) {
		$defaults = array('type' => 'stylesheet', 'inline' => true);
		$options += $defaults;

		if (is_array($path)) {
			$result = join("\n\t", array_map(array(&$this, __FUNCTION__), $path));
			return ($options['inline']) ? $result . "\n" : null;
		}
		$params = compact('path', 'options');
		$method = __METHOD__;

		$filter = function($self, $params, $chain) use ($defaults, $method) {
			extract($params);
			$type = $options['type'];
			$options = array_diff_key($options, $defaults);
			$template = ($type == 'import') ? 'style-import' : 'style-link';
			$params = compact('type', 'path', 'options');
			return $self->invokeMethod('_render', array($method, $template, $params));
		};
		$style = $this->_filter(__METHOD__, $params, $filter);

		if ($options['inline']) {
			return $style;
		}
		if ($this->_context) {
			$this->_context->styles($style);
		}
	}

	/**
	 * Creates a formatted <img /> element.
	 *
	 * @param string $path Path to the image file, relative to the app/webroot/img/ directory.
	 * @param array $options Array of HTML attributes.
	 * @return string
	 */
	public function image($path, $options = array()) {
		$defaults = array('alt' => '');
		$options += $defaults;
		$path = is_array($path) ? $this->_context->url($path) : $path;
		$params = compact('path', 'options');
		$method = __METHOD__;

		return $this->_filter($method, $params, function($self, $params, $chain) use ($method) {
			return $self->invokeMethod('_render', array($method, 'image', $params));
		});
	}

	/**
	 * Returns a formatted block tag, i.e <div />, <span />, <p />.
	 *
	 * @param string $name Tag name.
	 * @param string $content String content that will appear inside the div element.
	 *   If null, only a start tag will be printed
	 * @param array $options Additional HTML attributes of the DIV tag
	 * @return string The formatted tag element
	 */
	function tag($name, $content = null, $options = array()) {
		$options = is_array($options) ? $options : array('class' => $options);
		return $this->_render(__METHOD__, ($content === null) ? 'tag-start' : 'tag', compact(
			'name', 'options', 'content'
		));
	}

	/**
	 * Returns a formatted DIV tag for HTML FORMs.
	 *
	 * @param string $class CSS class name of the div element.
	 * @param string $content String content that will appear inside the div element.
	 *   If null, only a start tag will be printed
	 * @param array $options Additional HTML attributes of the DIV tag
	 * @return string The formatted DIV element
	 */
	function block($class = null, $content = null, $options = array()) {
		if ($class) {
			$options['class'] = $class;
		}
		return $this->_render(__METHOD__, 'block', compact('content', 'options'));
	}

	/**
	 * Returns a formatted P tag.
	 *
	 * @param string $class CSS class name of the p element.
	 * @param string $content String content that will appear inside the p element.
	 * @param array $options Additional HTML attributes of the P tag
	 * @return string The formatted P element
	 */
	function para($class, $content, $options = array()) {
		if ($class) {
			$options['class'] = $class;
		}
		return $this->_render(__METHOD__, ($content === null) ? 'para-start' : 'para', compact(
			'content', 'options'
		));
	}

	/**
	 * Creates a link to an external resource.
	 *
	 * @param string $type The title of the external resource
	 * @param mixed $url The address of the external resource or string for content attribute
	 * @param array $options Other attributes for the generated tag. If the type attribute
	 *               is 'html', 'rss', 'atom', or 'icon', the mime-type is returned.
	 * @return string
	 */
	protected function _metaLink($type, $url = null, $options = array()) {
		$options += isset($this->_metaLinks[$type]) ? $this->_metaLinks[$type] : array();

		if ($type == 'icon') {
			$url = $url ?: 'favicon.ico';
			$standard = $this->_render(__METHOD__, 'meta-link', compact('url', 'options'), array(
				'handlers' => array('url' => 'path')
			));
			$options['rel'] = 'shortcut icon';

			$ieFix = $this->_render(__METHOD__, 'meta-link', compact('url', 'options'), array(
				'handlers' => array('url' => 'path')
			));
			return "{$standard}\n\t{$ieFix}";
		}

		return $this->_render(__METHOD__, 'meta-link', compact('url', 'options'), array(
			'handlers' => array()
		));
	}
}

?>