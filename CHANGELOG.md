# Change Log

## v1.0.0

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

### Improved

- Improved database and source performance by adding caching of expensive lookups and 
  serveral other changes. 7d54eba, 8984bc2, bfc9426, 39ef580 (David Persson)
- Uniform __construct/__destruct docblocks. (David Persson)
- Improved overall API documentation. (David Persson) 
- Removed unused code (David Persson)
- Performance optimaziation of `Message::translate()` 28a2023 (David Persson)
- Performance optimization of `DocumentSet::_set()` #1144 (Warren Seymour)
- String::compare() will use native `hash_equals()` if possible. #1138 (David Persson)
- `Model::save()` using a relational database adapter will now only save updated fields,
  instead of blindly saving all. #1121 (Hamid Reza Koushki)
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

- `String::compare()` is now stricter and errors out when on of the provided params is not a string. 7822a2b (David Persson)
- The shorthand `Download` header has been deprecated because of its overhead and magicness. #1134 (David Persson)
- When updating an *existing* entity, validation rules for fields that are not present on the to-be-saved entity are
  skipped. The old behavior of requiring on *create and update* can be brought back by using the `'required'` option
  set to `true` with `Model::save()` or `Model::validates()`. #1118  (Hamid Reza Koushki)
- Encoding/decoding of JSON using empty values has been improved. When encoding `null` into JSON 
  an empty string instead of an empty array is returned. `{}` is now correctly decoded into an 
  empty array. #1103, #1090 (Simon Jaillet) 
- When setting Request/Response headers, the new headers are not returned directly. Instead
  one must now first set, then get. #1089 (David Persson) 
- Cache adapter instances cannot directly be filtered anymore, instead the `Cache` class' methods may
  be filtered. This clears up some misconceptions and simplifies adapter code. #1068 (David Persson)
- Strategy support for Cache::delete() has been removed as it made no sense. d15f3e7 (David Persson)
- Cache adapters must now extend the new cache adapter base class. (David Persson)
- Cache adapters must now support multi-key reads and writes. (David Persson)
- The APC cache adapter now extends the base cache adapter class. Thus the base class must be loaded
  before the APC adapter. Normally this should happen automatically and no updates are needed. However
  if you use an old bootstrap file, the base adapter may not been loaded before the apc one. 
  A deprecation notice will then be triggered. 

