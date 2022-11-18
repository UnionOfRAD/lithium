# Li3 2.0 Migration Guide

Li3 2.0 is a maximally-API-compatible upgrade from Li3 1.3 and 1.2, which provides support for PHP 8.1 and above. See the `CHANGELOG` for an exhaustive list of differences. What follows are guidelines for migrating application code.

## Base classes

Base classes `lithium\core\Object` and `lithium\core\StaticObject` (and their `*Deprecated` aliases) have been removed from the framework.

### Object initialization

The biggest impact of these changes is around the initialization of direct subclasses of `Object`. Note that this only affects _direct_ subclasses. Models, controllers, commands, etc. which extend their respective type-specific base classes are unaffected.

The functionality in `__construct()`, including auto-dispatching to `_init()`, has been migrated to the `lithium\core\AutoConfigurable` trait. As there are multiple ways to invoke its behavior, migration depends on the existing class, as laid out in the following scenarios:

**No class constructor**

If your class does not have its own constructor defined, and relied on `Object::__construct()`, simply `use` the `AutoConfigurable` trait as follows:

```php
<?php

use lithium\core\AutoConfigurable;

class MyClass {

    use AutoConfigurable;

   // ...
}

?>
```

If the class defines an `$_autoConfig` property, the existing automatic property configuration will continue to function as per normal, as will automatic dispatch to `_init()`, if defined.

(Note that the `AutoConfigurable` trait defines a stub `_init()` method for the sake of a consistent interface—it may be ignored).

**Class constructor defined**

If the class defines a constructor, it can invoke the `_autoConfig()` method manually, followed by the `_autoInit()` method.

```php
<?php

use lithium\core\AutoConfigurable;

class MyClass {

	use AutoConfigurable;

	public function __construct(array $config = []) {
		$defaults = [
			'foo' => null,
			'bar' => [],
		];
		$this->_autoConfig($config + $defaults, ['foo', 'bar' => 'merge']);
		$this->_autoInit($config);
	}
}

?>
```

Here, the configuration is passed, along with an array specifying which properties should be configured, in the same format as the `$_autoConfig` property. If `$_autoConfig` is defined in the class, it may be referenced directly, i.e.:

```php
$this->_autoConfig($config + $defaults, $this->_autoConfig);
```

Then, the class is initialized by passing `$config` to `_autoInit()`.

**‘Mixed’ initialization**

In some cases, classes generate their own config values, or pull values in from the environment to be merged with constructor-passed config.

In these cases, it is recommended to handle initialization per the following:

  1. Manually initialize `$this->_config` in the constructor
  2. Then, then call `_autoInit()`
  3. Handle custom initialization in `_init()`
  4. Finally, call `_autoConfig()`, passing `$this->_config` into it

Example:

```php
<?php

class MyClass {

	public function __construct($config = []) {
		$defaults = [
			'foo' => [],
		];
		$this->_config = $config + $defaults;
		$this->_autoInit($config);
	}

	protected function _init() {
		/**
		 * Handle custom initialization here
		 */

		$this->_autoConfig($this->_config, $this->_autoConfig);
	}
}

?>
```

### Defering class initialization

Prior to 2.0, class initialization (auto-dispatching to the `_init()` method) could be suppressed by passing `'init' => false` to the constructor. This has been replaced with a global constant that is defined in the `AutoConfigurable` trait:

```php
new MyClass([AUTO_INIT_CLASS => false])
```

Finally, as a result of this change, the `'init'` key is no longer automatically merged into the `$_config` property. Tests or other application logic depending on this behavior should be changed.

### Modifying `$_autoConfig` during class initialization

Previously, a subclass could modify its `$_autoConfig` property in the `_init()` method to have it take effect. Now, this must be done in `__construct()`, since automatic configuration is triggered by the constructor.

### Base class helper methods

Any base class helper methods used in application code may be swapped out per the following to maintain existing functionality:

- `_instance()`: Use `lithium\core\Libraries::instance()`
- `_parents()`: Use `class_parents()`
- `_stop()`: This has been directly implemented in `Controller` and `Command`, where it is most applicable. Re-implement directly if needed elsewhere (consider if a different approach may be more suitable)
- `invokeMethod()`: Use `call_user_func_array()`
- `respondsTo()`: Use `is_callable()`
  - `Model::respondsTo()`: Use `Model::hasFinder()`
  - `Validator::respondsTo()`: Use `Validator::has()`

### Unprotected `$_config` access

By virtue of `$_config` being defined in a base class common to all framework and application classes, a class could ‘reach across’ to any other class' `$_config` property and read data (or write, though this has always been against convention). This is no longer possible.

Workarounds include redefining `$_config` as `public` in affected subclasses (not recommended), providing access through a public method, or designing a proper interface to expose the specific values needed in a given context (recommended).

### Application config changes

If `require`ing core classes in `config/libraries.php`, apply the following patch to remove old base classes and add new traits. If you are migrating from a verison of Li3 prior to 1.2, this will look slightly different for you (i.e. `s/Deprecated//`):

```diff
-require LITHIUM_LIBRARY_PATH . '/lithium/core/ObjectDeprecated.php';
-require LITHIUM_LIBRARY_PATH . '/lithium/core/StaticObjectDeprecated.php';
+require LITHIUM_LIBRARY_PATH . '/lithium/core/AutoConfigurable.php';
+require LITHIUM_LIBRARY_PATH . '/lithium/core/MergeInheritable.php';
```

## Adapters

### `Database` adapters

If your application has a custom database adapter that directly extends `lithium\source\Database`, it must implement the protected method `_buildColumn()`, which accepts an array in the following format (example):

```php
[
  'name' => 'fieldname',
  'type' => 'string',
  'length' => 32,
  'null' => true,
  'comment' => 'test'
];
```

…and must return a field definition string that can be embedded in a `CREATE TABLE` statement.

This only applies to custom classes that _directly_ extend `lithium\source\Database`. Classes that extend concrete core database adapters (i.e. `MySql`, `PostgreSql`, etc.) do not require any changes.

### `Memcache` cache adapter

Previously, using `increment()` and `decrement()` on keys with non-numeric values would automatically cast those values to numbers. These are now no-op operations. It's recommended that values are checked beforehand, or cache schema enforcement is practiced.

### `Growl` log adapter

The `Growl` logger adapter has been removed, because the [Growl](https://growl.github.io/growl/) software is no longer supported.

The recommended replacement is [`jolicode/jolinotif`](https://github.com/jolicode/JoliNotif), a dedicated library providing cross-platform desktop messaging.

A simple shim adapter may be implemented to replace the Growl adapter in your application code, as follows:

```php
<?php

namespace myapp\extensions\adapter\analysis\logger;

use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;

class Growl {

  public $factory;

  public function __construct(array $config = []) {
    $this->factory = NotifierFactory::create();
  }

  public function write($priority, $message) {
    $this->factory->send(
      (new Notification())
      ->setTitle('My App')
      ->setBody($message)
    );
  }

}

?>
```
