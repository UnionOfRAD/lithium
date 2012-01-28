<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\g11n\multibyte\adapter;

/**
 * The `Mbstring` class is an adapter which uses certain string functions from
 * `ext/mbstring`. You will need to have the extension installed to use this adapter.
 *
 * No known limitations affecting used functionality. Silently strips
 * out badly formed UTF-8 sequences.
 *
 * @link http://php.net/manual/en/book.mbstring.php
 */
class Mbstring extends \lithium\core\Object {

	/**
	 * Determines if this adapter is enabled by checking if the `mbstring` extension is loaded.
	 *
	 * @return boolean Returns `true` if enabled, otherwise `false`.
	 */
	public static function enabled() {
		return extension_loaded('mbstring');
	}

	/**
	 * Here used as a multibyte enabled equivalent of `strlen()`.
	 *
	 * @link http://php.net/manual/en/function.mb-strlen.php
	 * @param string $string
	 * @return integer
	 */
	public function strlen($string) {
		return mb_strlen($string, 'UTF-8');
	}
}

?>