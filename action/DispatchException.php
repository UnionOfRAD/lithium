<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\action;

/**
 * This exception covers a range of scenarios that generally revolve around attempting to dispatch
 * to something which cannot handle a request, i.e. a controller which can't be found, objects
 * which aren't callable, or un-routable (private) controller methods.
 */
class DispatchException extends \RuntimeException {

	protected $code = 404;
}

?>