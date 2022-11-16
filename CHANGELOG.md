# Change Log

## v2.0.0

### Changed

- Dropped support for PHP 7.x

- The `Mongo` data source adapter has been updated to work with the `mongodb` extension
  instead of the old `mongo` extension. This brings back full li₃ support for MongoDB 
  on PHP 7.x. Due to how the new extension works, we do not support GridFS through the 
  adapter anymore. You can still access GridFS using the underlying connection object. 
  (Simon Jaillet, Dirk Brünsicke)

- Removed deprecated functionality: magic download header in action response, unit testing's
  expectException method. (David Persson) 

- ConfigExceptions (thrown when accessing non existent configurations) are now allowed to 
  bubble up in `Cache`.

- The following previously deprecated things have been removed:
	- The `XCache` `Cache` adapter
	- Glob brace support in `Libraries`
	- Per adapter filters
	- The `String` class
	- Long options like `--foo-bar` are now only made available as `fooBar` and
	  not `foo-bar` after parsing in the console router.
	- The test `Mocker` class
	- Support for `mcrypt` in the sesion `Encrypt` strategy
	- Support for old-style rendering instructions i.e. `['template' => '/path/to/template']`
	- The `FirePhp` logging adapter
	- Support for old-style AOP filters
	- Support for gernally retrieving class parents via `_parents()`, it is still possible to call
	  this method in subclasses of `Controller` and `Model`.
	- Support for generally halting executing via `_stop()`, it is still possible to call
	  this method in subclasses of `Controller` and `Command.
	- Support for generally using `__set_state()`.
	- Support for generally using `respondsTo()`.
	- Support for generally using `invokeMethod()`.
	- Support for generally using `_instance()`.
- The `Apc` adapter has been migrated from the traditional, now-unsupported APC extension, to [APCu](https://www.php.net/manual/en/book.apcu.php). No code changes are required—simply upgrade to the new extension.

## v1.2.0

### Added

- PHP 7.1 support

- PHP 7.2 partial support, please read the note on extending from `*Object` below

- PHP 7.3 partial support, please read the note on extending from `*Object` below

- The `li3_fixtures` library is now part of the framework `test` package.

- Data sources now support the `nested` strategy which will embed related records/documents by
  fetching them using additional queries. (jails) 
	
  This strategy is auto-enabled on NoSQL-style databases where documents now
  do not have to be fetched manually anymore. For classic relational databases
  the new strategy can be enabled on a per query basis, if preferred over the
  default `joined` strategy:

  ```
  Galleries::find('all', ['with' => 'Images', 'strategy' => 'nested'])
  ```

  Please note that records/documents - fetched using this strategy - cannot be
  further constrained by conditions.

- `Cache::key()` learned to generate safe cache keys. Where each adapter defines
  what "safe" means. The method was all in all made more flexible and feature rich
  as it added support for reusable and key mutating generator functions as well as
  support for key generation by passing any form of data.

- `action\Request` is now (via `$this->request->is('dnt')`) able to detect if the 
  _Do Not Track_ feature is enabled.

- The auto-library feature for scoped routes can now be disabled, so it's possible to
  i.e. have an app and an admin scope, where the admin scope itself contains several 
  admin libraries.

- `openssl_random_pseudo_bytes()` has been added as a new RNG source.

- Added `core\MergeInheritable` trait which allows classes to merge their array
  properties with such properties defined in class parents. Used mainly for
  merging `action\Controller::$_render` and `data\Model` properties. Also 
  slightly optimizes memory usage in said classes.

- The `Response` now knows about HTTP status code 426 (Upgrade Required).

- The `Encrypt` session strategy now uses the `openssl` extension for symmetric 
  encryption for better support and performance, whenever possible. Previously the,
  now deprecated, `mcrypt` extension was always used. When `openssl` cannot be 
  used as a drop in, the strategy will fall back to `mcrypt` usage (aka _legacy_ 
  mode). This is the case when a non-default cipher mode (anything else than AES 256 
  CBC) has been chosen or the `openssl` extension is not available.

- The credit card validator now supports MasterCard 2-series. (Grayson Scherer)

- `Libraries::instance()` now accepts a class map as a 4th parameter.

- `Helper::attributes()` is now part of the public API.

- `Model::hasFinder()` checks if a given finder is available, works for magic finders, too.

- `Validator::has()` checks if a rule is available under given name.

### Changed

- The undocumented feature in `Cache::{write,read,delete,increment,decrement}()`, where 
  these methods supported callables as keys has been removed. Keys can now be of scalar 
  type only.

- `Cache::key()` now requires a cache configuration name as it's first argument.

- Dropped support for PHP 5.5

- The `Encrypt` strategy now depends on the `openssl` extension, when it does not
  operate in _legacy_ mode (see above). In this case it also doesn't depend on the 
  `mcrypt` extension anymore.

- With HTML5 stating the type when linking or embedding scripts and styles using
  `text/css` and `text/javascript` has become unnecessary. The `Html` helper will 
  now generate `<link>`, `<style>` and `<script>` tags without such types.

### Deprecated

- Short rendering instructions have now been officially deprecated and trigger a
  deprecation message. Usage of short syntax was already discouraged.
  
  ```
  ['template' => '/path/to/template'] // short deprecated syntax
  ['template' => ['path' => '/path/to/template']] // full valid syntax
  ```

- `Object` and `StaticObject` are being deprecated, as
  `Object` is soft-reserved in PHP >=7. Chance is taken for a cleanup of the
  class-hirarchy and unused/obsolete methods. Newly created classes should
  not inherit from `Object`/`StaticObject` anymore. 

  When using the framework with PHP >=7.2, ensure you are extending
  `ObjectDeprecated` and `StaticObjectDeprecated`, for all other PHP versions
  this change is not required.
  
  | old | new |
  | --- | --- |
  | `*Object::$_autoConfig` | use `lithium\core\AutoConfigurable` |
  | `*Object::_init()` | use `lithium\core\AutoConfigurable` |
  | `*Object::_instance()` | replaced, use `lithium\core\Libraries::instance()` |
  | `analysis\Inspector::_instance()` |  replaced, use `lithium\core\Libraries::instance()` |
  | `data\Model::_instance()` |  replaced, use `lithium\core\Libraries::instance()` |
  | `*Object::_parents()` | replaced, use `lithium\core\MergeInheritable::_inherit()` |
  | `*Object::_stop()` | _no replacement_, must reimplement |
  | `Object::__set_state()` | _no replacement_ |
  | `*Object::invokeMethod()` | _no replacement_, use `call_user_func_array()` |
  | `analysis\Inspector::invokeMethod()` | _no replacement_ |
  | `Model::respondsTo()` | use `Model::hasFinder()` instead |
  | `Validator::respondsTo()` | use `Validator::has()` instead |
  | `*::respondsTo()` | use `is_callable()` instead |

- Changing the default cipher and/or mode for the `Encrypt` strategy has been 
  deprecated and will cause the strategy to switch into _legacy_ mode. In legacy
  mode the deprecated `mcrypt` extension will still be used.

- Deprecated the non-flatten mode in `Set::extract()` as it is rarely used.

### Fixed

- The `'key'` and `'class'` options were supposed to be provided only for
  Session strategies. They however leaked into Session adapters options.

- A potential invalid reuse of a previously initialized `mcrypt` resource
  has been fixed when using multiple `Encrypt` strategies with different
  ciphers and/or modes. 

## v1.1.1

### Added

- PHP 7.1 support

### Fixed

- Test report filters are now fully normalized before passing them into `Report`.
- Removed debug code in exception handling of `Database` (Alex Bowers).

## v1.1.0

### Added

- PHP 7.0 support

- `Database` now supports the `NOT BETWEEN` operator. #1208 (Eric Cholis)

- Restrictions on library search paths for i.e. adapters inside non-core libraries have been
  relaxed. This fixes an inconvenience where adapters (and other classes) always had to be placed
  under the `extensions` directory. Even in cases where it didn't feel natural to put them there.

  This is best demonstrated using the `li3_access` plugin as an example. This plugin
  defines a new adaptable class (`security\Access`).

  Before:
  ```
  li3_access
  ├── security
  │   ├── Access.php
  │   └── access
  │       └── Adapter.php
  └── extensions
      └── adapter
          └── access
              ├── Resources.php
              └── Rules.php
  ```

  After:
  ```
  li3_access
  └── security
      ├── Access.php
      └── access
          ├── Adapter.php
          └── adapter
              ├── Resources.php
              └── Rules.php
  ```


- Filters and related classes have been refactored and extracted into the new
  `lithium\aop` namespace.

  **Shallow hirarchy.** With the new `Filters`, classes that need filter functionality
  don't need to inherit from `Object`/`StaticObject` anymore and don't need to have
  special methods or properties defined. This is possible by entirely relying on a central
  filters manager and using `spl_object_hash()` internally.

  **Simplified filters context.** By integrating PHP 5.5's new `::class` keyword with `use`,
  references to the static context are now made with `Context::class`, removing the need
  for a static `$self` and repeating the full class name across filters in the same file.

  **Simplified filters signature.** By using PHP 5.4's new context binding feature for
  closures, we were able to simplify the signature of filters - i.e. by dropping the
  `$self`.

  This - as a sideeffect - reduces the requirement of using `invokeMethod()` to access
  protected members of the context. `$this` and `Context::class` can be used to access
  the filtered object. Also makes better stacktraces.

  **Simplified chain advancing.**
  Instead of advancing the chain via `$chain->next()`, a callable (`$next()`) is used.
  The advancing function just requires one argument.

  ```php
  function($self, $params, $chain) { // old
      $self->invokeMethod('_foo');
      $params['bar'] = 'baz';
      return $chain->next($self, $params, $chain);
  }

  function($params, $next) { // new
      $this->_foo();
      $params['bar'] = 'baz';
      return $next($params);
  }
  ```

  **Strict separation of filters and implementation.** The implementation function (the one
  being filtered) now takes just a single `$params` argument. It doesn't have access to
  the chain anymore.

  **Everything is lazy.** The new filter manager will now by default apply any filters -
  for both concrete and static classes - lazily.

  **Better filtering of concrete classes.** It is now possible to apply
  filters to both all and/or specific instances of a concrete class.

  ```php
  $foo = new Foo();

  // Executes filter only for `$foo`'s `bar` method.
  Filters::apply($foo, 'bar', function($params, $next) {});

  // Executes filter for all `Foo`'s `bar` methods.
  Filters::apply('Foo', 'bar', function($params, $next) {});
  ```

  **Performance** for filtered methods - especially when there are no filters applied to it -
  has been improved, so that there is only a minimal penality for making a method
  filterable.

- `Router::match()` now additionaly supports the magic schemes `tel` and `sms`. This
  allows to create `tel:+49401234567` style links.

- `Model::save()` using a relational database adapter will now only save updated fields,
  instead of blindly saving all. #1121 (Hamid Reza Koushki)

- `Hash::calculate()` learned to hash over arbitrary data (scalar and non-scalar, closures). #1196 (David Persson)

- Introduced new `lithium\net\HostString` class to help parse `<host>:<port>` strings.

- `Cache` together with the `File` adapter can now be used to store BLOBs. 

  ```php
  Cache::config([
    'blob' => [
      'adapter' => 'File', 
      'streams' => true // Enable this option for BLOB support.
    ]
  ]);
  
  $stream = fopen('php://temp', 'wb');
  $pdf->generate()->store($stream); // some expensive action
  
  // We must rewind the stream, as Cache will not do this for us.
  rewind($stream);
  
  // Store the contents of $stream into a cache item.
  Cache::write('blob', 'productCatalogPdf', $stream);
  
  // ... later somewhere else in the galaxy ...
  $stream = Cache::read('blob', 'productCatalogPdf');
  ```

- `mcrypt_create_iv()` and PHP7's `random_bytes()` have been added as new RNG sources.

- Enable custom error messages in form helper. This feature allows to provide messages
  for validation error inside the template. This allows easier translation of messages and
  customization in case there is no control over the model (i.e. developing a "theme" for a
  customer without changing the basic functionality). #1167 (David Persson)

- Strict mode can now be enabled for MySQL via the `'strict'` option. Read more about the
  feature at http://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sql-mode-strict. #1171 (David Persson)

- Added drain option to action request allowing to disable auto stream reading. When the
  drain option is disabled, action request will not drain stream data - when sent. This reduces
  memory usage and is a first step in enabling streaming very large files (i.e. uploading video
  content). The option is enabled by default to keep current behavior, but future versions may
  disable it. #1110 (David Persson)

  With draining disabled, streams must be read manually:

  ```php
  $stream = fopen('php://input', 'r');
  // Do something with $stream.
  fclose($stream);
  ```

  Note that with draining disabled, automatic content decoding is not supported
  anymore. It must be manually decoded as follows.

  ```php
  $data = Media::decode($request->type, stream_get_contents($stream));
  ```

- Text form fields now support generating corresponding `<datalist>` elements for autocompletion.

  ```php
  $this->form->field('region', [
      'type' => 'text',
      'list' => ['foo', 'bar', 'baz']
  ]);
  ```

- It is now guaranteed that `Random::generate()` will use a cryptographic strong RNG. It 
  no longer falls back to a less strong source. 

- Due to a new host string parser implementation and framework wide rollout, first 
  any class accepting a host string in the form of `<host>` or `<host>:<port>` now
  also accepts the port only notation `:<port>`. This allows to change just the
  port but keep using the default host name. Second, host strings will now also handle 
  IPv6 addresses correctly.

- Console command help now shows inherited options i.e. `[--silent] [--plain] [--help]`.

- Reduced `preg_match()` call count in `Router` in favor of `strpos()` for performance reasons.

- Improved database encoding, timezone and searchPath methods. #1172 (David Persson)

- Multi-word console command arguments are now parsed into camelized versions.
  `--no-color`, will be available as `noColor` and assigned to a `$noColor` property if
  present in the command class definition. Previously `--no-color` was made available as
  `no-color`. This has been deprecated.

- New `lithium\util\Text`, `lithium\security\Random` and `lithium\security\Hash` 
  classes which were extracted from `String`. #1184 (David Persson)

- The bulitin test framework now handles circular references in expectations or results
  correctly. The display format of fails has been changed to that of `print_r()`.

- Validator can now validate whole arrays:
  ```
  $value = array('complex' => true, 'foo' => 'bar');
  Validator::add('arrayHasComplexFooKeys', function($value, $format, $options) {
      return isset($value['complex'], $value['foo']);
  });
  ```

- Switched to short array syntax.

### Changed

- The `persist` option for the MongoDb adapter has been removed. Persistent connection
  configuration is not compatible with mongo ext >= 1.2.0. 1.2.0 was released in 
  2011 and it is expected that almost all users already have a more recent 
  version. (Eric Cholis)

- When failing to close an established network connection via the `Socket` subclasses 
  `Context`, `Stream` and `Curl` (i.e. `$socket->close()`), the close operation will
  not be retried anymore. Instead `false` is returned.

- To skip decoration in the console test command use `--just-assertions` instead of `--plain`.

- When no suitable RNG is found (rare), `Random::generate()` will throw an exception.

- Database encoding, timezone, and searchPath methods may now throw exceptions. Please
  wrap code calling these methods directly in try/catch blocks - if needed. #1172
  (David Persson)

- Instance filters are now not cleaned up automatically anymore, that
  is when the instance was destroyed, its filters went away with it.

- `Inspector::properties()` and `Inspector::methdos()` now requires an instance when 
  inspecting concrete classes.

- When calculating test coverage dead code is not ignored anymore. `XDEBUG_CC_DEAD_CODE`
  causes problems with PHP 7.0 + opcache and cannot be relieably used. 

- The undocumented and deprecated `'servers'` option in the `Memcache` cache adapter has been
  removed. `'host'` should be used in all cases.

### Deprecated

- Multi-word console command arguments i.e. `--no-color` were made available as
  `no-color`. This has been deprecated. 

- The XCache caching adapter has been deprecated as it is not compatible with the wildly
  deployed OPcache and does not perform better.

- The FirePhp logging adapter has been deprecated as Firebug's usage share is shrinking
  in favor of builtin developer tools.

- The builtin mocking framework (`lithium\test\Mocker`) has been deprecated as alternatives
  exist and it is not needed as a core test dependency. This takes the task of maintaining full
  blown mocking from us. You might want to have a look at 
  [Mockery](https://github.com/padraic/mockery).

- Per adapter filters in `Logger` and `Session` have been deprecated. Please apply filters 
  directly to the static `Logger::*` and `Session::*` methods instead.

  ```php
  // deprecated usage
  Session::config([
    'default' => [
       'filters' => [function($self, $params, $chain) { /* ... */ }]
    ]
  ]);

  // always use this
  use lithium\storage\Session;

  Filters::apply(Session::class, 'write', function($params, $next) { 
    /* ... */ 
  });
  ```

-  Methods in an adapter of `Logger` and `Session`, which returned a closure taking
  `$self` as the first parameter, should now drop that parameter expectation.

- The String class has been renamed to `Text` while RNG and hashing functionality
  have been extracted into `lithium\security\Random` and `lithium\security\Hash`. #1184 (David Persson)

  This is mainly to achieve PHP 7.0 compatibilty as `String`
  [will become a reserved name](https://wiki.php.net/rfc/reserve_even_more_types_in_php_7).

  Old methods are deprecated but continue to work and redirect to new methods. It wont be
  possible to use the old String class with PHP >= 7.0. You must use the new names before
  switching to PHP 7.0.

  | old | new |
  | --- | --- |
  | `lithium\util\String::hash()` | `lithium\security\Hash::calculate()` |
  | `lithium\util\String::compare()` | `lithium\security\Hash::compare()` |
  | `lithium\util\String::random()` | `lithium\security\Random::generate()` |
  | `lithium\util\String::ENCODE_BASE_64` | `lithium\security\Random::ENCODE_BASE_64` |
  | `lithium\util\String::uuid()` | `lithium\util\Text::uuid()` |
  | `lithium\util\String::insert()` | `lithium\util\Text::insert()` |
  | `lithium\util\String::clean()` | `lithium\util\Text::clean()` |
  | `lithium\util\String::extract()` | `lithium\util\Text::extract()` |
  | `lithium\util\String::tokenize()` | `lithium\util\Text::tokenize()` |

- The `lithium\util\collection\Filters` class has been deprecated in favor
  of `lithium\aop\Filters`.There have also been changes in how filters should
  be implemented and advanced. **Everything old keeps on working** and calls are
  forwarded to the new implementations. Methods for filtering functionality in
  `Object`/`StaticObject` have also been deprecated.

  | old | new |
  | --- | --- |
  | `lithium\core\*Object::applyFilter()` | `lithium\aop\Filters::apply()` |
  | `lithium\core\*Object::_filter()` | `lithium\aop\Filters::run()` |
  | `lithium\util\collection\Filters::apply()` | `lithium\aop\Filters::apply()` |
  | `lithium\util\collection\Filters::run()` | `lithium\aop\Filters::run()` |
  | `lithium\util\collection\Filters::hasApplied()` | `lithium\aop\Filters::hasApplied()` |

  **Changes in making a method filterable**:
  ```
  $this->_filter(__METHOD__, $params, function($self, $params) { // old (instance)
  static::_filter(__METHOD__, $params, function($self, $params) { // old (static)

  Filters::run($this, __FUNCTION__, $params, function($params) { // new (instance)
  Filters::run(get_called_class(), __FUNCTION__, $params, function($params) { // new (static)
  ```

  **Changes in applying filters**:
  ```
  $foo::applyFilter(/* ... */) // old (instance)
  Foo::applyFilter(/* ... */) // old (static)

  Filters::apply($foo, 'bar', /* ... */) // new (instance)
  Filters::apply('Foo', 'bar', /* ... */) // new (any instance)
  Filters::apply('Foo', 'bar', /* ... */) // new (static)
  ```

  **Changes in the filter signature**:
  ```php
  // old
  Filters::apply('Foo', 'bar', function($self, $params, $chain) {
    $self->invokeMethod('_qux');
    return $chain->next($self, $params, $chain);
  });

  // new
  Filters::apply('Foo', 'bar', function($params, $next) {
    $this->_qux();
    return $next($params);
  });
  ```

  Accessing the currently filtered class/method from inside a filter function
  (via `$chain->method()`) has been deprecated. 

### Fixed

- Fixed possible infinite retry loop when failing to close an established network 
  connection in the `Socket` subclasses `Context`, `Stream` and `Curl`.

- Fixed slug generation by `Inflector` for strings containing multibyte characters or
  (unprintable) whitespaces.

- Added missing uppercase transliteration pairs used by `Inflector::slug()`.

- Fixed edge case when using `Collection::prev()` and the collection contained
  a falsey value (i.e. `null`, `false`, `''`).

- Fixed parsing certain exception details in `Database` i.e. `pgsql unknown role exception`.

- Fixed retrieval of property default values in concrete classes through `Inspector`.

- Fixed write through caching via `Cache::read()`. When passing in a closure for the `'write'`
  option, the closure was called even when the key was already present in cache.

  - Fixed and enabled modification of the default query options through `Model::query()`.

## v1.0.3

### Fixed

- Fixed write through caching via `Cache::read()`. When passing in a closure for the `'write'`
  option, the closure was called even when the key was already present in cache.

- Fixed and enabled modification of the default query options through `Model::query()`.

## v1.0.2

### Deprecated

- Brace globbing support has been deprecated in `Libraries`. This feature
  cannot be reliably be provided crossplatform and will already not work
  if `GLOB_BRACE` is not available. (reported by Aaron Santiago)

### Fixed

- Optimized searching for a library's namespaces has been reenabled. 

- Per connection read preference settings for MongoDB were ignored. (Fitz Agard)

## v1.0.1

### Fixed

- Result-less queries produced by performing a raw query 
  i.e. `'SET SESSION group_concat_max_len = 1000000;'` are now handled correcly.

- Fixes MySQL DSN socket support when a socket path is given in `'host'`. 

- When using the model `count` finder without a `'conditions'` key, options
  like `having`, `offset`, `group` were mistakenly interpreted as conditions.

- Calculation queries returning no results at all, do not error out, but
  return `null` now.

- Extraction of translation tokens using context together with short array syntax
  is now fully supported.

## v1.0.0

(This includes changes from 1.0.0-beta on only.)

### Fixed

- Most coding standard violations have been fixed. (David Persson)
- Several incorrectly documented return/param types and unreachable code paths have been fixed, 
  thanks to our new static analysis tools. (David Persson)
- Fixed case where routes weren't matched inside scopes. #1115 (Hamid Reza Koushki)
- Several Security helper fixes #1131, f96e8b5, a868b3b, bbcadda (Hamid Reza Koushki, David Persson)
- Fixed Session::read() with HMAC strategy on non-existent key. #1111 (David Persson, Hamid Reza Koushki)
- Fixed bug where conditions on relation queries where not taken into account #1099, #1141 (Jacob Budin, David Persson, Hamid Reza Koushki)
- Fixed cases where values of some MongoDB query operatos were incorrectly casted. #1124 (Hamid Reza Koushki)
- Fixed case where `0` was be ignored as an argument value in commands. #1104, #1125 (Ali Farhadi)
- Fixed bug with mis-matched route default values #995, #1126 (Simon Jaillet)
- `$_FILES` parsing in Request is skipped if the globals option is off. 7c659c4 (David Persson)
- Fixed ignore of `$initial` parameter in `Collection::reduce()` #1096 (Jacob Budin)
- Fixed encoding/decoding of empty JSON values #1103, #1090 (Simon Jaillet) 
- The offset parameter in the cache memory adapter wasn't used. 1daa738 (David Persson)
- Compatiblity with latest MongoDB driver #1093, #1079, #698 (Simon Jaillet)
- Fixed tests to correctly run and pass on Windows #1073, #1039 (Tsoulloftas Christodoulos, Ciaro Vermeire) 
- Fixed case where custom configuration was ignored when using form helper with checkboxes #1061 (Mark Wilde)
- Fixed #888 to make the any option in Validator::check() work correctly. #1051 (David Persson)
- Fixed issue with compiled view templates on Windows. 32b878a (David Persson)
- Dechunking body data is now more safe and unwanted urldecoding of data is prevented. #1020 (Warren Seymour)
- Fixed bug when using LIMIT, ORDER and hasMany relations with PostgreSql. #1145 (Hamid Reza Koushki)
- Matched implementation with documentation of `Request::accepts()`. The method now returns a boolean 
  when type is provided. #1180, #856 (David Persson, David Rogers)
- Fixed bug where route defaults weren't kept when key params were present in route. (David Persson)
- Fixed several bugs in the FormSignature class. #839, #998, #1173 (Hamid Reza Koushki, David Persson, Ciaro Vermeire, cinaeco)
- Fixed undetected buggy behavior in `Database`/`RecordSet` where records could get out of order and **associated has-many** 
  records would be partially missing. The bug occurred when querying for results with a has-many relationship and without ordering 
  by the primary key of the main record. In all queries involving a has-many relationship, it is adviced to first order by the main 
  id as otherwise an exception will be thrown. 
  We've decided against adding in the main id magically, as silently rewriting users' queries is a rabbit hole we dont't want 
  to go down. In general implicit order by the database is not something to rely on, if you want records in a certain order, always 
  explictly specify it. 
  Fixes #1162 and #1182 (Hamid Reza Koushki, Nate Abele, David Persson)

  ```php
  Posts::find('all', array( // wrong :(
	'order' => 'Comments.created',
	'with' => 'Comments'
  ));

  Posts::find('all', array( // correct :)
	'order' => array('id', 'Comments.created'),
	'with' => 'Comments'
  ));

  Posts::find('all', array( // correct :)
	'order' => array('title', 'id', 'Comments.created'),
	'with' => 'Comments'
  ));
  ```

### Improved

- Improved database and source performance by adding caching of expensive lookups and 
  serveral other changes. 7d54eba, 8984bc2, bfc9426, 39ef580 (David Persson)
- Uniform __construct/__destruct docblocks. (David Persson)
- Improved overall API documentation. (David Persson) 
- Removed unused code (David Persson)
- Performance optimization of `Message::translate()` 28a2023 (David Persson)
- Performance optimization of `DocumentSet::_set()` #1144 (Warren Seymour)
- Removed most `extract()`-usage 651d07a, f74b691 (David Persson)
- String::compare() will use native `hash_equals()` if possible. #1138 (David Persson)
- Better cookie support in Request/Response #618, #1123 (Ali Farhadi)
- We're now using standard tripple-backtick markdown syntax for fenced code blocks. (David Persson)
- When using `isset()` nested documents are now supported using the dot notation #1094 (Warren Seymour)
- Improved casting of MongoDB array based fields as content elements. #1102 (Simon Jaillet)
- Improved performance in net related classes #1089 (David Persson)
- When using the cache adapter for logging, logs are now persisted as long as possible. d3b2382 (David Persson)
- Better HHVM compatibilty (although not yet full) 0de8fa0, 651d07a (David Persson)
- Micro-optimization of `is_null()` into `=== null`. aa4ea12 (David Persson)
- Implemented GC and increment/decrement for file cache adapter. (David Persson)
- Improved file cache adapter parser. (David Persson)
- Improved host/port parsing in redis cache adapter. #1076 (Soban Vuex)
- Added missing 429, 431, 511 HTTP status codes. #1075 (Dirk Brünsicke)
- `session_cache_limiter` is now configurable. 7a98128 (David Persson)
- Enable serialization of data entity and collection. (David Persson)
- Cast empty dates to `NULL` for relational databases. #997 (Simon Jaillet)
- Use transactions when bulk-inserting in redis cache adapter. 995214f (David Persson)
- Improved view template cache GC and compilation. 6f641e7, a502da5 (David Persson)
- Support DELETE method in curl socket #1034 (Warren Seymour)
- Improved performance by using HMAC in the FormSignature class. #1173 (David Persson)
- Improved memory profile and (internal) semantics of `RecordSet` and PDO `Result` fetching. (Nate Abele)
- Order direction using `Database` are now normalized to uppercase (i.e. `ASC`). (David Person)

### Added

- Add support for PATCH HTTP method. (Gavin Davies)
- Allow prefixed names for auth check (i.e. `user.name`) (Alex Soban)
- Allow continued manipulation in dispatcher rules. #1158, #1159 (David Persson, Hamid Reza Koushki)
- Added change log file. You're reading it :) (David Persson)
- Added whitelist support to `Model::validates()` #1143 (Ali Farhadi)
- Added support for atomic increment/decrement in relational databases #1122 (Ali Farhadi)
- Added three-way `'required'` switch option for controlling when to run validation rules against 
  (non-existent) fields. Now by default all fields that are required on *create* keep being required (unchanged),
  however when *updating* validation rules for fields that are not present are skipped. #1118 (Hamid Reza Koushki)
- Added support for REGEXP and SOUNDS LIKE operators for MySQL #1116 (David Persson)
- Coverage summary statistics are now shown when using the test command #1107 (Gavin Davies)
- Added support for using sockets in redis cache adapter. #1076 (Soban Vuex)
- Implemented cache scoping. #1067 (David Persson)
- Added cache adapter base class. (David Persson)
- Implemented cache item persistence through `Cache::PERSIST`. 8a0b24a (David Persson)
- The g11n infrastructure now supports gettext-like contexts. #999 (Jasper Tey) 

### Changed

- The shorthand `Download` header has been deprecated because of its overhead and magicness. #1134 (David Persson)
- When updating an *existing* entity, validation rules for fields that are not present on the to-be-saved entity are
  skipped. The old behavior of requiring on *create and update* can be brought back by using the `'required'` option
  set to `true` with `Model::save()` or `Model::validates()`. #1118  (Hamid Reza Koushki)
- Strategy support for Cache::delete() has been removed as it made no sense. d15f3e7 (David Persson)
- The APC cache adapter now extends the base cache adapter class. Thus the base class must be loaded
  before the APC adapter. Normally this should happen automatically and no updates are needed. However
  if you use an old bootstrap file, the base adapter may not been loaded before the apc one.
  A deprecation notice will then be triggered. (David Persson)

### Backwards Incompatible Changes

- Removed the automatic `__init()` for static classes. fa4ef11 (jails)
- `String::compare()` is now stricter and errors out when one of the provided params is not a string. 7822a2b (David Persson)
- Encoding/decoding of JSON using empty values has been improved. When encoding `null` into JSON
  an empty string instead of an empty array is returned. `{}` is now correctly decoded into an
  empty array. #1103, #1090 (Simon Jaillet)
- When setting Request/Response headers, the new headers are not returned directly. Instead
  one must now first set, then get. #1089 (David Persson)
- Cache adapter instances cannot directly be filtered anymore, instead the `Cache` class' methods may
  be filtered. This clears up some misconceptions and simplifies adapter code. #1068 (David Persson)
- Cache adapters must now extend the new cache adapter base class. (David Persson)
- Cache adapters must now support multi-key reads and writes. (David Persson)
- When used with a parameter `lithium\action\Request::accepts()` now returns a boolean.
  It previously was returning i.e. `'json'` when `application/json` was in accepted content types
  and invoked with `Request::accepts('json')`. This change matches the actual behavior with
  documented (and expected) behavior. (David Persson, David Rogers)
- The `library` command has been extracted into the `li3_lab` plugin. Please install the plugin
  to continue using that command. #1174 (David Persson)
- The `FormSignature` class now uses HMAC with a secret key. This will now require configuring the class
  with an app specific secret key before using it. #1173 (David Persson)
- When installed via composer the default location is now `libraries/lithium`.
- Test `test` command no longer modifies the `error_reporting` setting. Please make sure
  you have set the `error_reporting` to `E_ALL` in your php.ini.
