<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util\collection;

/**
 * The `Filters` class is the basis of Lithium's method filtering system: an efficient way to enable
 * event-driven communication between classes without tight coupling and without depending on a
 * centralized publish/subscribe system.
 *
 * In Lithium itself, when creating a method that can be filtered, a method is implemented as a
 * [ closure](http://us2.php.net/manual/en/functions.anonymous.php) and is passed to either
 * `Object::_filter()` or `StaticObject::_filter()`. Each object internally maintains its own list
 * of filters, which are applied in these methods and passed to `Filters::run()`.
 *
 * When implementing a custom filter system outside of Lithium, you can create your own list of
 * filters, and pass it to `$options['items']` in the `run()` method.
 *
 * When creating a filter to apply to a method, you need the name of the method you want to call,
 * along with a **closure**, that defines what you want the filter to do.  All filters take the same
 * 3 parameters: `$self`,`$params`, and `$chain`.
 *
 * - `$self`: If the filter is applied on an object instance, then `$self` will be that instance. If
 * applied to a static class, then `$self` will be a string containing the fully-namespaced class
 * name.
 *
 * - `$params`: Contains an associative array of the parameters that are passed into the method. You
 * can modify or inspect these parameters before allowing the method to continue.
 *
 * - `$chain`: Finally, `$chain` contains the list of filters in line to be executed (as an
 * instance of the `Filters` class).  At the bottom of `$chain` is the method itself.  This is why
 * most filters contain a line that looks like this:
 *
 * {{{return $chain->next($self, $params, $chain);}}}
 *
 * This passes control to the next filter in the chain, and finally, to the method itself.  This
 * allows you to interact with the return value as well as the parameters.
 *
 * Within the framework, you can call `applyFilter()` on any object (static or instantiated) and
 * pass the name of the method you would like to filter, along with the filter itself. For example:
 *
 * {{{use \lithium\action\Dispatcher;
 *
 * Dispatcher::applyFilter('run', function($self, $params, $chain) {
 * 	// Custom pre-dispatch logic goes here
 * 	$response = $chain->next($self, $params, $chain);
 *
 * 	// $response now contains a Response object with the result of the dispatched request,
 * 	// and can be modified as appropriate
 * 	// ...
 * 	return $response;
 * });}}}
 *
 * The logic in the closure will now be executed on every call to `Dispatcher::run()`, and
 * `$response` will always be modified by any custom logic present before being returned from
 * `run()`.
 *
 * @see lithium\util\collection\Filters::run()
 * @see lithium\core\Object::_filter()
 * @see lithium\core\StaticObject::_filter()
 * @see lithium\core\Object::applyFilter()
 * @see lithium\core\StaticObject::applyFilter()
 */
class Filters extends \lithium\util\Collection {

	protected $_autoConfig = array('items', 'class', 'method');

	protected $_class = null;

	protected $_method = null;

	/**
	 * Collects a set of filters to iterate. Creates a filter chain for the given class/method,
	 * executes it, and returns the value.
	 *
	 * @param mixed $class The class for which this filter chain is being created. If this is the
	 *        result of a static method call, `$class` should be a string. Otherwise, it should
	 *        be the instance of the object making the call.
	 * @param array $params An associative array of the given method's parameters.
	 * @param array $options The configuration options with which to create the filter chain.
	 *        Mainly, these options allow the `Filters` object to be queried for details such as
	 *        which class / method initiated it. Available keys:
	 *
	 *        -'class': The name of the class that initiated the filter chain.
	 *        -'method': The name of the method that initiated the filter chain.
	 *        -'items': An array of callable objects (usually closures) to be iterated through.
	 *          By default, execution will be nested such that the first item will be executed
	 *          first, and will be the last to return.
	 * @return Returns the value returned by the first closure in `$options['items`]`.
	 */
	public static function run($class, $params, array $options = array()) {
		$defaults = array('class' => null, 'method' => null, 'items' => array());
		$chain = new Filters($options + $defaults);
		$next = $chain->rewind();
		return $next($class, $params, $chain);
	}

	/**
	 * Provides short-hand convenience syntax for filter chaining.
	 *
	 * @param object $self The object instance that owns the filtered method.
	 * @param array $params An associative array containing the parameters passed to the filtered
	 *              method.
	 * @param array $chain The Filters object instance containing this chain of filters.
	 * @return mixed Returns the return value of the next filter in the chain.
	 * @see lithium\core\Object::applyFilter()
	 * @see lithium\core\Object::_filter()
	 * @todo Implement checks allowing params to be null, to traverse filter chain
	 */
	public function next($self, $params, $chain) {
		if (empty($self) || empty($chain)) {
			return parent::next();
		}
		$next = parent::next();
		return $next($self, $params, $chain);
	}

	/**
	 * Gets the method name associated with this filter chain.  This is the method being filtered.
	 *
	 * @param boolean $full Whether to return the method name including the class name or not.
	 * @return string
	 */
	public function method($full = false) {
		return $full ? $this->_class . '::' . $this->_method : $this->_method;
	}
}

?>