<?php namespace lithium\core;

use lithium\util\collection\Filters;

trait Filterable
{
    /**
	 * Contains a 2-dimensional array of filters applied to this object's methods, indexed by method
	 * name. See the associated methods for more details.
	 *
	 * @see lithium\core\Filterable::_filter()
	 * @see lithium\core\Filterable::applyFilter()
	 * @var array
	 */
	protected $_methodFilters = array();

    /**
	 * Apply a closure to a method of the current object instance.
	 *
	 * @see lithium\core\Filterable::_filter()
	 * @see lithium\util\collection\Filters
	 * @param mixed $method The name of the method to apply the closure to. Can either be a single
	 *        method name as a string, or an array of method names. Can also be false to remove
	 *        all filters on the current object.
	 * @param \Closure $filter The closure that is used to filter the method(s), can also be false
	 *        to remove all the current filters for the given method.
	 * @return void
	 */
	public function applyFilter($method, $filter = null) {
		if ($method === false) {
			$this->_methodFilters = array();
			return;
		}
		foreach ((array) $method as $m) {
			if (!isset($this->_methodFilters[$m]) || $filter === false) {
				$this->_methodFilters[$m] = array();
			}
			if ($filter !== false) {
				$this->_methodFilters[$m][] = $filter;
			}
		}
	}

    /**
	 * Executes a set of filters against a method by taking a method's main implementation as a
	 * callback, and iteratively wrapping the filters around it. This, along with the `Filters`
	 * class, is the core of Lithium's filters system. This system allows you to "reach into" an
	 * object's methods which are marked as _filterable_, and intercept calls to those methods,
	 * optionally modifying parameters or return values.
	 *
	 * @see lithium\core\Filterable::applyFilter()
	 * @see lithium\util\collection\Filters
	 * @param string $method The name of the method being executed, usually the value of
	 *               `__METHOD__`.
	 * @param array $params An associative array containing all the parameters passed into
	 *              the method.
	 * @param \Closure $callback The method's implementation, wrapped in a closure.
	 * @param array $filters Additional filters to apply to the method for this call only.
	 * @return mixed Returns the return value of `$callback`, modified by any filters passed in
	 *         `$filters` or applied with `applyFilter()`.
	 */
	protected function _filter($method, $params, $callback, $filters = array()) {
		list($class, $method) = explode('::', $method);

		if (empty($this->_methodFilters[$method]) && empty($filters)) {
			return $callback($this, $params, null);
		}

		$f = isset($this->_methodFilters[$method]) ? $this->_methodFilters[$method] : array();
		$data = array_merge($f, $filters, array($callback));
		return Filters::run($this, $params, compact('data', 'class', 'method'));
	}
}