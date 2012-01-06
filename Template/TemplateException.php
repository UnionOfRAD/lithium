<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace Lithium\Template;

/**
 * A `TemplateException` is thrown whenever a view template cannot be found, or a called template is
 * not readable or accessible for rendering. Also used by the view compiler if a compiled template
 * cannot be written.
 */
class TemplateException extends \RuntimeException {

	protected $code = 500;
}

?>