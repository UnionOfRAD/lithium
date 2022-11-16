<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2010, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\net;

use ReflectionClass;
use ReflectionProperty;
use lithium\core\AutoConfigurable;

/**
 * Base message class for any URI based request/response.
 *
 * @link http://tools.ietf.org/html/rfc3986#section-1.1.1
 * @link http://en.wikipedia.org/wiki/URI_scheme#Generic_syntax
 */
class Message {

	use AutoConfigurable;

	/**
	 * The URI scheme.
	 *
	 * @var string
	 */
	public $scheme = 'tcp';

	/**
	 * The hostname for this endpoint.
	 *
	 * @var string
	 */
	public $host = 'localhost';

	/**
	 * The port for this endpoint.
	 *
	 * @var string
	 */
	public $port = null;

	/**
	 * The username for this endpoint.
	 *
	 * @var string
	 */
	public $username = null;

	/**
	 * The password for this endpoint.
	 *
	 * @var string
	 */
	public $password = null;

	/**
	 * Absolute path of the request.
	 *
	 * @var string
	 */
	public $path = null;

	/**
	 * The body of the message.
	 *
	 * @var mixed
	 */
	public $body = null;

	/**
	 * Constructor Adds config values to the public properties when a new object is created.
	 *
	 * @param array $config Available configuration options are:
	 *        - `'scheme'` _string_: 'tcp'
	 *        - `'host'` _string_: 'localhost'
	 *        - `'port'` _integer_: null
	 *        - `'username'` _string_: null
	 *        - `'password'` _string_: null
	 *        - `'path'` _string_: null
	 *        - `'body'` _mixed_: null
	 * @return void
	 */
	public function __construct(array $config = []) {
		$defaults = [
			'scheme' => 'tcp',
			'host' => 'localhost',
			'port' => null,
			'username' => null,
			'password' => null,
			'path' => null,
			'body' => null
		];
		$config += $defaults;

		$this->_autoConfig($config, isset($this->_autoConfig) ? $this->_autoConfig : []);

		foreach (array_intersect_key(array_filter($config), $defaults) as $key => $value) {
			$this->{$key} = $value;
		}
		$this->_autoInit($config);
	}

	/**
	 * Add body parts and compile the message body.
	 *
	 * @param mixed $data
	 * @param array $options
	 *        - `'buffer'` _integer_: split the body string
	 * @return array
	 */
	public function body($data = null, $options = []) {
		$default = ['buffer' => null];
		$options += $default;
		$this->body = array_merge((array) $this->body, (array) $data);
		$body = join("\r\n", $this->body);
		return ($options['buffer']) ? str_split($body, $options['buffer']) : $body;
	}

	/**
	 * Converts the data in the record set to a different format, i.e. an array. Available
	 * options: array, url, context, or string.
	 *
	 * @param string $format Format to convert to.
	 * @param array $options
	 * @return mixed
	 */
	public function to($format, array $options = []) {
		switch ($format) {
			case 'array':
				$array = [];
				$class = new ReflectionClass(get_class($this));

				foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
					$array[$prop->getName()] = $prop->getValue($this);
				}
				return $array;
			case 'url':
				$host = $this->host . ($this->port ? ":{$this->port}" : '');
				return "{$this->scheme}://{$host}{$this->path}";
			case 'context':
				$defaults = ['content' => $this->body(), 'ignore_errors' => true];
				return [$this->scheme => $options + $defaults];
			case 'string':
			default:
				return (string) $this;
		}
	}

	/**
	 * Magic method to convert object to string.
	 *
	 * @return string
	 */
	public function __toString() {
		return (string) $this->body();
	}
}

?>