# li₃ PHP Framework

li₃ is a fast, flexible PHP framework built for PHP 7.4+ with support for PHP 8.x. It provides a comprehensive MVC architecture with support for multiple databases including MySQL, PostgreSQL, SQLite, MongoDB, CouchDB, Redis, and Memcached.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Initial Setup
- Verify PHP version: `php --version` (supports PHP 7.4-8.x)
- Framework is operational without any installation - just clone and use
- **DO NOT run `composer install`** - the framework currently requires PHP 7.4 in composer.json but works with PHP 8.x

### Console Commands (li₃)
- Navigate to framework root or application root 
- Use `php console/lithium.php` or `./console/li3` for console commands
- Get help: `php console/lithium.php help` or `php console/lithium.php help <command>`
- Available commands: `create`, `test`, `route`, `help`, `g11n`

### Creating Applications
- Copy the test app structure: `cp -r tests/ci/app /path/to/your-app/`
- Link lithium library: `ln -s /path/to/lithium your-app/libraries/lithium`
- **NEVER CANCEL**: All creation commands complete in under 0.05 seconds

#### Generate Code
From within an application directory:
- Create controller: `php libraries/lithium/console/lithium.php create controller Posts`
- Create model: `php libraries/lithium/console/lithium.php create model Posts`
- Create everything: `php libraries/lithium/console/lithium.php create Posts`
- Create tests: `php libraries/lithium/console/lithium.php create test controller Posts`

### Testing
**NEVER CANCEL**: Test commands typically complete in under 0.1 seconds. Always wait for completion.

#### Basic Testing
- Run all tests: `php console/lithium.php test tests/cases`  
- Run specific test: `php console/lithium.php test tests/cases/data/CollectionTest.php`
- Run with verbose output: `php console/lithium.php test tests/cases --verbose`

#### Database-Specific Testing
For applications with database connections:
- SQLite (no setup): `DB=sqlite php libraries/lithium/console/lithium.php test libraries/lithium/tests/cases/data`
- MySQL: `DB=mysql php libraries/lithium/console/lithium.php test libraries/lithium/tests/cases/data`
- PostgreSQL: `DB=pgsql php libraries/lithium/console/lithium.php test libraries/lithium/tests/cases/data`
- CouchDB: `DB=couchdb php libraries/lithium/console/lithium.php test libraries/lithium/tests/cases/data`

**TIMING**: Individual test files: 0.05s, Test suites: 0.06-0.1s, Full framework tests: 0.1s

### Routes and URLs
- List routes: `php libraries/lithium/console/lithium.php route all`
- Test route: `php libraries/lithium/console/lithium.php route show /posts`
- Routes require configuration in `config/routes.php` (not present by default)

## Validation
Always manually validate changes by running through these scenarios:

### Basic Validation Workflow
1. **Create a test application**:
   ```bash
   cp -r tests/ci/app /tmp/test-app
   cd /tmp/test-app
   ln -s /home/runner/work/lithium/lithium libraries/lithium
   ```

2. **Test console functionality**:
   ```bash
   php libraries/lithium/console/lithium.php help
   ```

3. **Generate and test code**:
   ```bash
   php libraries/lithium/console/lithium.php create controller TestController
   php libraries/lithium/console/lithium.php create model TestModel
   php libraries/lithium/console/lithium.php create test controller TestController
   ```

4. **Run tests**:
   ```bash
   php libraries/lithium/console/lithium.php test tests/cases/controllers/TestControllerTest.php
   ```

### Framework Testing
Always run framework tests after making core changes:
```bash
php console/lithium.php test tests/cases/data/CollectionTest.php
```

## Dependencies and Extensions

### Required PHP Extensions
Available and working in standard environment:
- `curl` - For HTTP requests
- `mysqli`, `pdo_mysql` - For MySQL database adapter
- `pdo_pgsql` - For PostgreSQL database adapter  
- `pdo_sqlite`, `sqlite3` - For SQLite database adapter
- `mongodb` - For MongoDB database adapter
- `redis` - For Redis cache adapter
- `memcached` - For Memcached cache adapter

### Optional Extensions
- `openssl` - For encrypted sessions
- `mcrypt` - Legacy encryption support
- `xdebug` - For test coverage (development only)

### Database Services
When using CI/integration testing setup:
- MySQL: `mysql:5` with user `root`, password `password`
- PostgreSQL: `postgres` with trust authentication
- MongoDB: `mongo` (default config)
- Redis: `redis:4` (default config) 
- CouchDB: `couchdb:2` (default config)
- Memcached: `memcached` (default config)

## Common Tasks

### Repository Structure
```
console/          - Command line tools (li3)
action/           - Controllers and HTTP handling  
data/             - Models and database abstraction
template/         - Views and templating system
storage/          - Caching and session storage
net/              - HTTP and socket networking
core/             - Core framework classes
util/             - Utility classes
tests/            - Framework test suite
  cases/          - Unit tests
  integration/    - Integration tests
  ci/             - CI setup and test app
```

### Key Files to Know
- `console/lithium.php` - Main console entry point
- `core/Libraries.php` - Library and class loading
- `data/Model.php` - Base model class
- `action/Controller.php` - Base controller class
- `template/View.php` - Template rendering
- `tests/ci/app/` - Example application structure

### Framework Philosophy
- **PSR-4 compatible** - Easy integration with other libraries
- **Adapter pattern** - Swap databases, caches, etc. easily
- **Filter system** - Intercept and modify behavior via closures
- **No compilation required** - Pure PHP, runs immediately
- **Convention over configuration** - Minimal setup required

## Troubleshooting

### Common Issues
- **Test errors**: Some framework tests may fail due to method signature mismatches - this is known and doesn't affect functionality
- **Composer conflicts**: Framework works with PHP 8.x despite composer.json requiring 7.4
- **Missing routes**: Applications need route configuration in `config/routes.php`
- **Database connections**: Use environment variables like `DB=sqlite` to configure test database connections

### Performance Notes  
- Framework operations are very fast (under 0.1s for most commands)
- No build step required - code changes are immediately active
- Tests run quickly for rapid development feedback
- Use `--verbose` flag for detailed test output when debugging