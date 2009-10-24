<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\util\collection;

class Filters extends \lithium\util\Collection {

	protected $_autoConfig = array('items', 'class', 'method');

	protected $_class = null;

	protected $_method = null;

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
		return parent::next()->__invoke($self, $params, $chain);
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