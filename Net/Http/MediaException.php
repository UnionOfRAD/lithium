<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Net\Http;

/**
 * The `MediaException` is thrown when a request is made to render content in a format not
 * supported.
 *
 * @see Lithium\Net\Http\Media
 */
class MediaException extends \RuntimeException {

	protected $code = 415;
}

?>