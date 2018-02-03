<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2015, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\aop;

/**
 * The `Chain` class represents a collection of callables, called filters.
 * Instances contain a list of filters in line to be executed. It is often
 * used in conjunction with the `Filters` class.
 *
 * Filters wrap around each other and then finally around the implementation.
 * While the first added filter is called with the input first, and last in
 * receiving the result.
 *
 * ```asciiart
 *        │                ▲
 *        │                │
 * ┌──────┼────────────────┼──────┐
 * │      │    Filter 1    │      │
 * │      │                │      │
 * │ ┌────┼────────────────┼────┐ │
 * │ │    │    Filter 2    │    │ │
 * │ │    │                │    │ │
 * │ │┌───┼────────────────┼───┐│ │
 * │ ││   │ Implementation │   ││ │
 * │ ││   ▼                │   ││ │
 * │ ││                        ││ │
 * │ │└────────────────────────┘│ │
 * │ └──────────────────────────┘ │
 * └──────────────────────────────┘
 * ```
 *
 * Filters executed inside the chain receive two parameters, the named
 * parameters and the instance of the chain itself. Filters may _advance the
 * chain_ by one `$next($params)` anywhere inside their function body. Filters
 * can interrupt the chain by returning without advancing the chain.
 *
 * An example filter modifying the named parameters before advancing the chain.
 * ```
 * function($params, $next) {
 *     $params['foo'] = 'bar';
 *     return $next($params);
 * }
 * ```
 *
 * This class separates concerns as follows. It is context-less and knows
 * nothing about the class/method it filters. This is the task of the `Filters`
 * manager class. Concepts of _filter_ and _implementation_ are clearly
 * separated, too. Filters take two arguments, the implementation one. The
 * implementation does not know about it being part of the `Chain` and has no
 * access to it.
 */
class Chain {

	/**
	 * An array of callables.
	 *
	 * @var array
	 */
	protected $_filters = [];

	/**
	 * The current implementation.
	 *
	 * @see lithium\aop\Chain::run()
	 * @var callable
	 */
	protected $_implementation = null;

	/**
	 * Constructor.
	 *
	 * @param array $config Class configuration parameters The available options are:
	 *         - `'filters'` _array_
	 * @return void
	 */
	public function __construct(array $config = []) {
		$config += ['filters' => []];
		$this->_filters = $config['filters'];
	}

	/**
	 * Runs the chain causing any queued callables and finally the
	 * implementation to be executed.
	 *
	 * Before each run the implementation is made available to
	 * `Chain::__invoke()` and the chain rewinded, after each run the
	 * implementation is unset.
	 *
	 * An example implementation which is bound to an instance receives exactly
	 * one argument (the named parameters).
	 * ```
	 * function($params) {
	 *     $foo = $this->_bar;
	 *     return $foo . 'baz';
	 * }
	 * ```
	 *
	 * @param array $params An array of named parameters.
	 * @param callable $implementation
	 * @return mixed The end result of the chain.
	 */
	public function run(array $params, $implementation) {
		$this->_implementation = $implementation;

		$filter = reset($this->_filters);
		$result = $filter($params, $this);

		$this->_implementation = null;
		return $result;
	}

	/**
	 * Advances the chain by one and executes the next filter in line. This
	 * method is usually accessed from within a filter function.
	 *
	 * This method is implemented as a magic method as it allows use to hide the
	 * fact that the second parameter passed to filters is a rich object. Making
	 * the single purpose (next) very clear.
	 *
	 * A filter function using `$next` inside its function body to advance the
	 * chain.
	 * ```
	 * function($params, $next) {
	 *     return $next($params);
	 * }
	 * ```
	 *
	 * @see lithium\aop\Chain::next()
	 * @param array $params An array of named parameters.
	 * @return mixed The return value of the next filter. If there is no
	 *         next filter, the return value of the implementation.
	 */
	public function __invoke(array $params) {
		if (($filter = next($this->_filters)) !== false) {
			return $filter($params, $this);
		}

		$implementation = $this->_implementation;
		return $implementation($params);
	}
}

?>