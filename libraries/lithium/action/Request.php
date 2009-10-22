<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\action;

use \lithium\util\Validator;

class Request extends \lithium\core\Object {

	public $url = null;

	public $params = array();

	public $data = array();

	public $query = array();

	/**
	 * Holds the environment variables for the request. Retrieved with env().
	 *
	 * @var array
	 * @see lithium\http\Request::env()
	 */
	protected $_env = array();

	protected $_type = 'html';

	protected $_base = null;

	protected $_classes = array('media' => '\lithium\http\Media');

	protected $_detectors = array(
		'mobile'  => array('HTTP_USER_AGENT', null),
		'ajax'    => array('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest'),
		'flash'   => array('HTTP_USER_AGENT', 'Shockwave Flash'),
		'ssl'     => 'HTTPS'
	);

	/**
	 * Content-types accepted by the client.  If extension parsing is enabled in the
	 * Router, and an extension is detected, the corresponding content-type will be
	 * used as the overriding primary content-type accepted.
	 *
	 * @var array
	 */
	protected $_acceptTypes = array();

	protected $_autoConfig = array('classes' => 'merge', 'detectors' => 'merge', 'base', 'type');

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
		$this->_base = $this->_base ?: $this->_base();

		$m  = '/(iPhone|MIDP|AvantGo|BlackBerry|J2ME|Opera Mini|DoCoMo|NetFront|Nokia|PalmOS|';
		$m .= 'PalmSource|portalmmm|Plucker|ReqwirelessWeb|SonyEricsson|Symbian|UP\.Browser|';
		$m .= 'Windows CE|Xiino)/i';
		$this->_detectors['mobile'][1] ?: $m;

		$this->url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
		$this->url = $this->url ?: '/';
		$this->_env = (array)$_SERVER + (array)$_ENV;

		$envs = array('isapi' => 'IIS', 'cgi' => 'cgi');
		$env = php_sapi_name();
		$this->_env['PLATFORM'] = array_key_exists($env, $envs) ? $envs[$env] : null;

		if (!empty($_POST)) {
			$this->data = $_POST;

			if (!empty($this->_env['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
				$this->data['_method'] = $this->_env['HTTP_X_HTTP_METHOD_OVERRIDE'];
			}

			if (isset($this->data['_method'])) {
				if (isset($_SERVER) && !empty($_SERVER)) {
					$_SERVER['REQUEST_METHOD'] = $params['data']['_method'];
				} else {
					$_ENV['REQUEST_METHOD'] = $params['data']['_method'];
				}
				unset($this->params['form']['_method']);
			}
		}

		if (!empty($_FILES)) {
			foreach ($_FILES as $name => $data) {
				if ($name != 'data') {
					$params['form'][$name] = $data;
				}
			}

			// Replace this:
			if (isset($_FILES['data'])) {
				foreach ($_FILES['data'] as $key => $data) {
					foreach ($data as $model => $fields) {
						foreach ($fields as $field => $value) {
							if (is_array($value)) {
								$params['data'][$model][$field][key($value)][$key] = current($value);
							} else {
								$params['data'][$model][$field][$key] = $value;
							}
						}
					}
				}
			}
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
			if (array_key_exists($this->_env['HTTPS'])) {
				return (isset($this->_env['HTTPS']) && $this->_env['HTTPS'] == 'on');
			}
			return (strpos($this->_env['SCRIPT_URI'], 'https://') === 0);
		}

		if ($key == 'SCRIPT_NAME') {
			if ($this->_env['PLATFORM'] == 'CGI' || isset($this->_env['SCRIPT_URL'])) {
				$key = 'SCRIPT_URL';
			}
		}
		$val = null;

		if (!array_key_exists($key, $this->_env)) {
			$val = getenv($key);
		} else {
			$val = $this->_env[$key];
		}

		if ($key == 'REMOTE_ADDR' && $val == $this->env('SERVER_ADDR')) {
			if (($addr = $this->env('HTTP_PC_REMOTE_ADDR')) != null) {
				$val = $addr;
			}
		}

		if ($val !== null && $val !== false) {
			return $val;
		}

		switch ($key) {
			case 'SCRIPT_FILENAME':
				if ($this->_env['PLATFORM'] == 'IIS') {
					return str_replace('\\\\', '\\', $this->env('PATH_TRANSLATED'));
				}
			break;
			case 'DOCUMENT_ROOT':
				$fileName = $this->env('SCRIPT_FILENAME');
				$offset = (!strpos($this->env('SCRIPT_NAME'), '.php')) ? 4 : 0;
				$offset = strlen($fileName) - (strlen($this->env('SCRIPT_NAME')) + $offset);
				return substr($fileName, 0, $offset);
			break;
			case 'PHP_SELF':
				return str_replace($this->env('DOCUMENT_ROOT'), '', $this->env('SCRIPT_FILENAME'));
			break;
			case 'CGI':
			case 'CGI_MODE':
				return ($this->_env['PLATFORM'] == 'CGI');
			break;
			case 'HTTP_BASE':
				return preg_replace ('/^([^.])*/i', null, $this->_env['HTTP_HOST']);
			break;
		}
		return null;
	}

	/**
	 * undocumented function
	 *
	 * @param string $key
	 * @return void
	 */
	public function get($key) {
		list($var, $key) = explode(':', $key);

		switch (true) {
			case in_array($var, array('params', 'data', 'query')):
				return isset($this->{$key[0]}[$key[1]]) ? $this->{$key[0]}[$key[1]] : null;
			break;
			case ($var == 'env'):
				return $this->env($key);
			break;
		}
		return null;
	}

	/**
	 * Detects properties of the request and returns a boolean response
	 *
	 * @return boolean
	 * @see lithium\http\Request::detect()
	 * @todo Remove $content and refer to Media class instead
	 */
	public function is($flag) {
		$flag = strtolower($flag);
		$content = array('xml', 'rss', 'atom');

		if (array_key_exists($flag, $this->_detectors)) {
			$detector = $this->_detectors[$flag];

			if (is_array($detector)) {
				if (is_string($detector[1]) && Validator::isRegex($detector[1])) {
					return (bool)preg_match($detector[1], $this->env($detector[0]));
				}
				return ($this->env($detector[0]) == $detectors[1]);
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
	 * @see lithium\http\Request::is()
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
		if (!empty($ref) && defined('FULL_BASE_URL')) {
			$base = FULL_BASE_URL . $this->webroot;
			if (strpos($ref, $base) === 0) {
				$return =  substr($ref, strlen($base));
				if ($return[0] != '/') {
					$return = '/'.$return;
				}
				return $return;
			} elseif (!$local) {
				return $ref;
			}
		}
		return ($default != null) ? $default : '/';
	}

	public function normalizeFiles($original) {
		$r = array();

		$files = array();

		foreach ($original as $file) {
			if (!is_array($file['name'])) {
				$files[] = $file;
				continue;
			}
			$nested = array();

			foreach ($file as $key => $items) {
				while (is_array(current($items))) {
					$items = $items[key($items)];
				}
				$items = array_values($items);

				foreach ($items as $i => $item) {
					$nested[$i][$key] = $item;
				}
			}
			$files = array_merge($files, $nested);
		}
		return $files;

		foreach ($files as $field => $values) {
			if (!is_array($values['name'])) {
				$r[$field] = $values;
				continue;
			}
			foreach ($values as $key => $fields) {
				while ($fields) {
					foreach ($fields as $tmpField => $val) {
						unset($fields[$tmpField]);
						if (!is_array($val)) {
							$tmpField = preg_replace('/(^[^\[]+)(.*)/', '[\\1]\\2', $tmpField);
							$r[$field . $tmpField][$key] = $val;
							continue;
						}
						foreach ($val as $subField => $subVal) {
							$fields[$tmpField . '[' . $subField . ']'] = $subVal;
						}
					}
				}
			}
		}

		foreach ($r as $field => $values) {
			$r[$field] = compact('field') + $values;
		}
		return array_values($r);
	}

	/**
	 * @todo Replace string directory names with configuration
	 * @return void
	 */
	protected function _base() {
		$base = dirname($this->env('PHP_SELF'));
		if ($base === '/') {
			return null;
		}
		while (in_array(basename($base), array('app', 'webroot'))) {
			$base = ltrim(dirname($base), '.');
		}
		return rtrim($base, '/');
	}
}

?>