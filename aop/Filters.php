<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 *
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace lithium\aop;

use lithium\aop\Chain;

/**
 * The `Filters` class centrally manages all filters and together with `Chain`
 * forms the basis of the filtering system.
 *
 * The filters system is the innovative, no-nonsense and streamlined take on AOP:
 * an efficient way to enable event-driven communication between classes without
 * tight coupling.
 *
 * ## Making a Method Filterable
 *
 * Many methods inside the framework are already filterable and marked with
 * a `@filter` docblock tag.
 *
 * To make a method filterable you first wrap the implementation inside a
 * closure, create a named array of the parameters, then use `Filters::run()`.
 *
 * ```
 * class Foo {
 *	public function bar($name) {
 *		return "Hello {$name}!";
 *	}
 * }
 * ```
 *
 * ... turns into  ..
 *
 * ```
 * use lithium\aop\Filters;
 *
 * class Foo {
 *	public function bar($name) {
 *		return Filters::run($this, __FUNCTION__, compact('name'), function($params) {
 *			return "Hello {$params['name']}!";
 *		});
 *	}
 * }
 * ```
 *
 * ## Creating a Filter
 *
 * A filter can be any callable, but usually is a closure. It always takes two
 * parameters: `$params` and `$next`, both are explained below.
 *
 * ```
 * function($params, $next) {
 *	// Do something before ...
 *	$result = $next($params);
 *	// Do something after ...
 *	return $result;
 * };
 * ```
 *
 * `$params` contains an associative array of the parameters that are passed
 * into the implementation. You can modify or inspect these parameters before
 * allowing the filter to continue.
 *
 * `$next` allows you to pass control to the next filter
 * in the chain and finally when to the implementation itself. This allows you
 * to interact with the return value as well as the parameters.
 *
 * ## Applying a Filter
 *
 * Filters are applied using `Filters::apply()`. The method needs the class
 * and method which should be filtered as well as a filter to apply.
 *
 * Filters can be applied to both static or instantiated objects.
 *
 * Here we apply a filter to the `Dispatcher`, where we want to run custom
 * logic before `Dispatcher::run()` executes. The logic in the filter will be
 * executed on every call to `Dispatcher::run()`, and `$response` will always be
 * modified by any custom logic present before being returned from `run()`.
 * ```
 * use lithium\aop\Filters;
 * use lithium\action\Dispatcher;
 *
 * Filters::apply(Dispatcher::class, 'run', function($params, $next) {
 * 	// Custom pre-dispatch logic goes here.
 * 	$response = $next($params);
 *
 * 	// $response now contains a Response object with the result of the
 * 	// dispatched request, and can be modified as appropriate.
 * 	return $response;
 * });
 * ```
 *
 * @link https://en.wikipedia.org/wiki/Aspect-oriented_programming
 * @link http://php.net/functions.anonymous.php
 * @see lithium\aop\Chain
 */
class Filters {

	/**
	 * An array of filters keyed by their class and method id.
	 * Will be used to later construct `Chain` objects, per class and method.
	 *
	 * @see lithium\aop\Filters::_ids()
	 * @see lithium\aop\Filters::_chain()
	 * @var array
	 */
	protected static $_filters = [];

	/**
	 * Holds `Chain` objects keyed by their primary class and method id.
	 *
	 * @see lithium\aop\Filters::_ids()
	 * @see lithium\aop\Filters::_chain()
	 * @array
	 */
	protected static $_chains = [];

	/**
	 * Lazily applies a filter to a method.
	 *
	 * Classes aliased via `class_alias()` are treated as entirely separate from
	 * their original class.
	 *
	 * When calling apply after previous runs (rarely happens), this method will
	 * invalidate the chain cache.
	 *
	 * Multiple applications of a filter will add the filter multiple times to
	 * the chain. It is up to the user to keep the list of filters unique.
	 *
	 * This method intentionally does not establish class context for closures
	 * by binding them to the instance or statically to the class. Closures can
	 * originate from static and instance methods and PHP does not allow to
	 * rebind a closure from a static method to an instance.
	 *
	 * @param string|object $class The fully namespaced name of a static class or
	 *        an instance of a concrete class to which the filter will be applied.
	 *        Passing a class name for a concrete class will apply the filter to all
	 *        instances of that class.
	 * @param string $method The method name to which the filter will be applied i.e. `'bar'`.
	 * @param callable $filter The filter to apply to the class method. Can be anykind of
	 *        a callable, most often this is a closure.
	 * @return void
	 */
	public static function apply($class, $method, $filter) {
		list($id,) = static::_ids($class, $method);

		if (!isset(static::$_filters[$id])) {
			static::$_filters[$id] = [];
		}
		static::$_filters[$id][] = $filter;

		if (isset(static::$_chains[$id])) {
			unset(static::$_chains[$id]);
		}
	}

	/**
	 * Checks to see if the given class/method has any filters applied.
	 *
	 * @param string|object $class Fully namespaced class name or an instance of a class.
	 * @param string $method The method name i.e. `'bar'`.
	 * @return boolean
	 */
	public static function hasApplied($class, $method) {
		foreach (static::_ids($class, $method) as $id) {
			if (isset(static::$_filters[$id])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Runs the chain and returns its result value. This method is used to make
	 * a method filterable.
	 *
	 * All filters in the run will have access to given parameters. The
	 * implementation will be placed as the last item in the chain, so
	 * that effectively filters for the implementation wrap arround its
	 * implementation.
	 *
	 * Creates `Chain` objects lazily, caches and reuses them with differing
	 * parameters for best of both worlds: lazy object construction to save
	 * upfront memory as well as quick re-execution. This method may be called
	 * quite often when filtered methods are executed inside a loop. Thus it
	 * tries to reduce overhead as much as possible. Optimized for the common
	 * case that no filters for a filtered method are present.
	 *
	 * An example implementation function:
	 * ```
	 * function($params) {
	 *     $params['foo'] = 'bar';
	 *     return $params['foo'];
	 * }
	 * ```
	 *
	 * Two examples to make a method filterable.
	 * ```
	 * // Inside a static method.
	 * Filters::run(get_called_class(), __FUNCTION__, $params, function($params) {
	 *     return 'implementation';
	 * });
	 *
	 * // Inside an instance method.
	 * Filters::run($this, __FUNCTION__, $params, function($params) {
	 *     return 'implementation';
	 * });
	 * ```
	 *
	 * @see lithium\aop\Chain
	 * @see lithium\aop\Chain::run()
	 * @param string|object $class The fully namespaced name of a static class or
	 *        an instance of a concrete class. Do not pass a class name for
	 *        concrete classes. For instances will use a set of merged filters.
	 *        First class filter, then instance filters.
	 * @param string $method The method name i.e. `'bar'`.
	 * @param array $params
	 * @param callable $implementation
	 * @return mixed The result of running the chain.
	 */
	public static function run($class, $method, array $params, $implementation) {
		$implementation = static::_bcImplementation($class, $method, $params, $implementation);

		if (!static::hasApplied($class, $method)) {
			return $implementation($params);
		}
		return static::_chain($class, $method)->run($params, $implementation);
	}

	/**
	 * Clears filters optionally constrained by class or class and method combination.
	 *
	 * To clear filters for all methods of static class:
	 * ```
	 * Filters::clear('Foo');
	 * ```
	 *
	 * To clear instance and class filters for all methods of concrete class,
	 * or to clear just the instance filters for all methods:
	 * ```
	 * Filters::clear('Bar');
	 * Filters::clear($instance);
	 * ```
	 *
	 * This method involves some overhead. This is neglectable as it isn't commonly
	 * called in hot code paths.
	 *
	 * @param string|object $class Fully namespaced class name or an instance of a class.
	 * @param string $method The method name i.e. `'bar'`.
	 * @return void
	 */
	public static function clear($class = null, $method = null) {
		if ($class === null && $method === null) {
			static::$_filters = static::$_chains = [];
			return;
		}

		if (is_string($class)) {
			$regex  = '^<' . str_replace('\\', '\\\\', ltrim($class, '\\')) . '.*>';
		} else {
			$regex  = '^<.*#' . spl_object_hash($class) . '>';
		}
		if ($method) {
			$regex .= "::{$method}$";
		}
		foreach (preg_grep("/{$regex}/", array_keys(static::$_filters)) as $id) {
			unset(static::$_filters[$id]);
		}
		foreach (preg_grep("/{$regex}/", array_keys(static::$_chains)) as $id) {
			unset(static::$_chains[$id]);
		}
	}

	/**
	 * Calculates possible ids for a class/method combination. Normalizes
	 * leading backslash in class name by removing it.
	 *
	 * In general instances have two possible ids and static classes have one.
	 * The id is formattet according to the following pattern which is inspired
	 * by the format used by `psysh`:
	 * ```
	 * <foo\Bar #0000000046feb0630000000176a1b630>::baz
	 * <lithium\action\Dispatcher>::run
	 * ```
	 *
	 * @link http://psysh.org/
	 * @param string|object $class Fully namespaced class name or an instance of a class.
	 * @param string $method The method name i.e. `'bar'`.
	 * @return array An array of the possible ids.
	 */
	protected static function _ids($class, $method) {
		if (is_string($class)) {
			return ['<' . ltrim($class, '\\') . ">::{$method}"];
		}
		return [
			'<' . get_class($class) . ' #' . spl_object_hash($class) . ">::{$method}",
			'<' . get_class($class) . ">::{$method}"
		];
	}

	/**
	 * Creates a chain for given class/method combination or retrieves it from
	 * cache. Will implictly do a reverse merge to put static filters first before
	 * instance filters.
	 *
	 * @see lithium\aop\Chain
	 * @param string|object $class Fully namespaced class name or an instance of a class.
	 * @param string $method The method name i.e. `'bar'`.
	 * @return \lithium\aop\Chain
	 */
	protected static function _chain($class, $method) {
		$ids = static::_ids($class, $method);

		if (isset(static::$_chains[$ids[0]])) {
			return static::$_chains[$ids[0]];
		}
		$filters = [];

		foreach ($ids as $id) {
			if (isset(static::$_filters[$id])) {
				$filters = array_merge(static::$_filters[$id], $filters);
			}
		}
		return static::$_chains[$ids[0]] = new Chain(compact('class', 'method', 'filters'));
	}

	/* Deprecated / BC */

	public static function bcRun($class, $method, array $params, $implementation, array $filters) {
		$implementation = static::_bcImplementation($class, $method, $params, $implementation);
		$ids = static::_ids($class, $method);

		foreach ($ids as $id) {
			if (isset(static::$_filters[$id])) {
				$filters = array_merge(static::$_filters[$id], $filters);
			}
		}
		return new Chain(compact('class', 'method', 'filters'));
	}

	protected static function _bcImplementation($class, $method, $params, $implementation) {
		$reflect = new \ReflectionFunction($implementation);

		if ($reflect->getNumberOfParameters() > 1) {
			$message  = 'Old style implementation function in file ' . $reflect->getFileName() . ' ';
			$message .= 'on line ' . $reflect->getStartLine() . '. ';
			$message .= 'The signature for implementation functions has changed. It is now ';
			$message .= '`($params)` instead of the old `($self, $params)`. ';
			$message .= 'Instead of `$self` use `$this` or `static`.';
			trigger_error($message, E_USER_DEPRECATED);

			$implementation = function($params) use ($class, $implementation) {
				return $implementation($class, $params, null);
			};
		}
		return $implementation;
	}
}

?>