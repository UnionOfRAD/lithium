<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\action;

use \lithium\util\Validator;

/**
 * Request is responsible for identifying and storing all the information about the http request.
 *
 * @see lithium\action\Dispatcher
 */
class Request extends \lithium\core\Object {

	/**
	 * current url of request
	 *
	 * @var string
	 */
	public $url = null;

	/**
	 * params for request
	 *
	 * @var array
	 */
	public $params = array();

	/**
	 * POST data
	 *
	 * @var data
	 */
	public $data = array();

	/**
	 * GET data
	 *
	 * @var string
	 */
	public $query = array();

	/**
	 * base path
	 *
	 * @var string
	 */
	protected $_base = null;

	/**
	 * Holds the environment variables for the request. Retrieved with env().
	 *
	 * @var array
	 * @see lithium\action\Request::env()
	 */
	protected $_env = array();

	/**
	 * request type
	 *
	 * @var string
	 */
	protected $_type = 'html';

	/**
	 * classes used
	 *
	 * @var array
	 */
	protected $_classes = array('media' => '\lithium\http\Media');

	/**
	 * options used to detect request type
	 *
	 * @var string
	 */
	protected $_detectors = array(
		'mobile'  => array('HTTP_USER_AGENT', null),
		'ajax'    => array('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest'),
		'flash'   => array('HTTP_USER_AGENT', 'Shockwave Flash'),
		'ssl'     => 'HTTPS',
		'get'     => array('REQUEST_METHOD', 'GET'),
		'post'    => array('REQUEST_METHOD', 'POST'),
		'put'     => array('REQUEST_METHOD', 'PUT'),
		'delete'  => array('REQUEST_METHOD', 'DELETE'),
		'head'    => array('REQUEST_METHOD', 'HEAD'),
		'options' => array('REQUEST_METHOD', 'OPTIONS')
	);

	/**
	 * Content-types accepted by the client.  If extension parsing is enabled in the
	 * Router, and an extension is detected, the corresponding content-type will be
	 * used as the overriding primary content-type accepted.
	 *
	 * @var array
	 */
	protected $_acceptTypes = array();

	/**
	 * auto configuration properties
	 *
	 * @var array
	 */
	protected $_autoConfig = array(
		'classes' => 'merge', 'env' => 'merge', 'detectors' => 'merge', 'base', 'type'
	);

	/**
	 * Pulls request data from superglobals.
	 *
	 * @return void
	 * @todo Replace $_FILES loops with Felix's code (or Marc's?)
	 * @todo Consider disabling magic quotes stripping, or only having it explicitly enabled, since
	 *       it's deprecated now.
	 */
	protected function _init() {
		parent::_init();

		$m  = '/(iPhone|MIDP|AvantGo|BlackBerry|J2ME|Opera Mini|DoCoMo|NetFront|Nokia|PalmOS|';
		$m .= 'PalmSource|portalmmm|Plucker|ReqwirelessWeb|SonyEricsson|Symbian|UP\.Browser|';
		$m .= 'Windows CE|Xiino)/i';
		$this->_detectors['mobile'][1] = $m;

		$this->url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
		$this->url = $this->url ?: '/';
		$this->_env += (array)$_SERVER + (array)$_ENV;

		$envs = array('isapi' => 'IIS', 'cgi' => 'CGI', 'cgi-fcgi' => 'CGI');
		$this->_env['PLATFORM'] = isset($envs[PHP_SAPI]) ? $envs[PHP_SAPI] : null;
		$this->_base = $this->_base ?: $this->_base();
		$this->query = isset($_GET) ? $_GET : array();
		$this->data = isset($_POST) ? $_POST : array();

		if (isset($this->data['_method'])) {
			$this->_env['HTTP_X_HTTP_METHOD_OVERRIDE'] = $this->data['_method'];
			unset($this->data['_method']);
		}

		if (isset($this->_env['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
			$this->_env['REQUEST_METHOD'] = $this->_env['HTTP_X_HTTP_METHOD_OVERRIDE'];
		}

		if (isset($_FILES)) {
			$result = array();
			$normalize = function($key, $value) use ($result, &$normalize){
				foreach ($value as $param => $content) {
					foreach ($content as $num => $val) {
						if (is_numeric($num)) {
							$result[$key][$num][$param] = $val;
							continue;
						}
						if (is_array($val)) {
							foreach ($val as $next => $one) {
								$result[$key][$num][$next][$param] = $one;
							}
							continue;
						}
						$result[$key][$num][$param] = $val;
					}
				}
				return $result;
			};
			foreach ($_FILES as $key => $value) {
				if (isset($value['name'])) {
					if (is_string($value['name'])) {
						$result[$key] = $value;
						continue;
					}
					if (is_array($value['name'])) {
						$result += $normalize($key, $value);
					}
				}
			}
			$this->data += $result;
		}
	}

	/**
	 * Allows request parameters to be accessed as object properties, i.e. `$this->request->action`
	 * instead of `$this->request->params['action']`.
	 *
	 * @param string $name The property name/parameter key to return.
	 * @return mixed Returns the value of `$params[$name]` if it is set, otherwise returns null.
	 * @see lithium\action\Request::$params
	 */
	public function __get($name) {
		if (isset($this->params[$name])) {
			return $this->params[$name];
		}
	}

	/**
	 * Queries PHP's environment settings, and provides an abstraction for standardizing expected
	 * environment values across varying platforms, as well as specify custom environment flags.
	 *
	 * @param string $key
	 * @return void
	 * @todo Refactor to lazy-load environment settings
	 */
	public function env($key) {
		if ($key == 'base') {
			return $this->_base;
		}

		if ($key == 'HTTPS') {
			if (isset($this->_env['HTTPS'])) {
				return (!empty($this->_env['HTTPS']) && $this->_env['HTTPS'] !== 'off');
			}
			return (strpos($this->_env['SCRIPT_URI'], 'https://') === 0);
		}

		if ($key == 'SCRIPT_NAME') {
			if ($this->_env['PLATFORM'] == 'CGI' || isset($this->_env['SCRIPT_URL'])) {
				$key = 'SCRIPT_URL';
			}
		}

		$val = array_key_exists($key, $this->_env) ? $this->_env[$key] : getenv($key);
		$this->_env[$key] = $val;

		if ($key == 'REMOTE_ADDR' && $val == $this->env('SERVER_ADDR')) {
			$val = ($addr = $this->env('HTTP_PC_REMOTE_ADDR')) ? $addr : $val;
		}

		if ($val !== null && $val !== false) {
			return $val;
		}

		switch ($key) {
			case 'SCRIPT_FILENAME':
				if ($this->_env['PLATFORM'] == 'IIS') {
					return str_replace('\\\\', '\\', $this->env('PATH_TRANSLATED'));
				}
				return $this->env('PHP_SELF');
			case 'DOCUMENT_ROOT':
				$fileName = $this->env('SCRIPT_FILENAME');
				$offset = (!strpos($this->env('SCRIPT_NAME'), '.php')) ? 4 : 0;
				$offset = strlen($fileName) - (strlen($this->env('SCRIPT_NAME')) + $offset);
				return substr($fileName, 0, $offset);
			case 'PHP_SELF':
				return str_replace('\\', '/', str_replace(
					$this->env('DOCUMENT_ROOT'), '', $this->env('SCRIPT_FILENAME')
				));
			case 'CGI':
			case 'CGI_MODE':
				return ($this->_env['PLATFORM'] == 'CGI');
			case 'HTTP_BASE':
				return preg_replace ('/^([^.])*/i', null, $this->_env['HTTP_HOST']);
		}
	}

	/**
	 * Get params, data, query or env
	 *
	 * @param string $key data:title, env:base
	 * @return void
	 */
	public function get($key) {
		list($var, $key) = explode(':', $key);

		switch (true) {
			case in_array($var, array('params', 'data', 'query')):
				return isset($this->{$var}[$key]) ? $this->{$var}[$key] : null;
			case ($var == 'env'):
				return $this->env($key);
		}
		return null;
	}

	/**
	 * Detects properties of the request and returns a boolean response
	 *
	 * @return boolean
	 * @see lithium\action\Request::detect()
	 * @todo Remove $content and refer to Media class instead
	 */
	public function is($flag) {
		$flag = strtolower($flag);

		if (isset($flag, $this->_detectors)) {
			$detector = $this->_detectors[$flag];

			if (is_array($detector)) {
				if (is_string($detector[1]) && Validator::isRegex($detector[1])) {
					return (bool)preg_match($detector[1], $this->env($detector[0]));
				}
				return ($this->env($detector[0]) == $detector[1]);
			} elseif (is_object($detector)) {
				return $detector($this);
			}
			return (bool)$this->env($detector);
		}
		return false;
	}

	/**
	 * Returns the content type of the response.
	 *
	 * @return string A simple content type name, i.e. `'html'`, `'xml'`, `'json'`, etc., depending
	 *         on the content type of the request.
	 */
	public function type() {
		return $this->_type;
	}

	/**
	 * Creates a 'detector' used with Request::is().  A detector is a boolean check that is created
	 * to determine something about a request.
	 *
	 * @return void
	 * @see lithium\action\Request::is()
	 */
	public function detect($flag, $detector = null) {
		if (is_array($flag)) {
			$this->_detectors = $flag + $this->_detectors;
		} else {
			$this->_detectors[$flag] = $detector;
		}
	}

	/**
	 * Gets the referring URL of this request
	 *
	 * @param string $default Default URL to use if HTTP_REFERER cannot be read from headers
	 * @param boolean $local If true, restrict referring URLs to local server
	 * @return string Referring URL
	 * @todo Rewrite me to remove constant dependencies
	 */
	function referer($default = null, $local = false) {
		$ref = $this->env('HTTP_REFERER');
		if (!empty($ref)) {
			if (!$local) {
				return $ref;
			}
			if (strpos($ref, '://') == false) {
				return $ref;
			}
		}
		return ($default != null) ? $default : '/';
	}

	/**
	 * @todo Replace string directory names with configuration
	 * @return void
	 */
	protected function _base() {
		$base = str_replace('\\', '/', dirname($this->env('PHP_SELF')));
		return rtrim(str_replace(array('/app/webroot', '/webroot'), '', $base), '/');
	}
}

?>