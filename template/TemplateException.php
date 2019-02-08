<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\template;

/**
 * A `TemplateException` is thrown whenever a view template cannot be found, or a called template is
 * not readable or accessible for rendering. Also used by the view compiler if a compiled template
 * cannot be written.
 */
class TemplateException extends \RuntimeException {

	protected $code = 500;
}

?>