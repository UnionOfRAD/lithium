# li₃

### You asked for a better framework. Here it is.

li₃ is the fast, flexible and the most RAD development framework for PHP.

### A framework of firsts

li₃ is the first and only major PHP framework built from the ground up for PHP
5.3+, and the first to break ground into major new technologies, including
bridging the gap between relational and non-relational databases through a
single, unified API.

### Promiscuously opinionated

Some frameworks give you a solid set of classes, but little or no default
project organization, leaving you to fend for yourself on each project you
create, and spend time wiring up framework classes that should just work
together. Others provide you with great organizational conventions, but no way
to break out of those conventions if you need to, and too often, no way to
override or replace core framework classes.

li₃ is the first framework to give you the best of both worlds, without
compromising either. In fact, li₃'s API is intentionally designed to allow you
to "grow out of" the framework and into your own custom code over the course of
your application's lifecycle, if your needs require.

### Technology

li₃ takes full advantage of the latest PHP features, including
namespaces, late static binding and closures. li₃'s innovative [method filter
system](http://li3.me/docs/lithium/util/collection/Filters) makes extensive use
of closures and anonymous functions to allow application developers to "wrap"
framework method calls, intercepting parameters before, and return values after.

li₃ also complies with PSR-4, the PHP namespacing standard, allowing you to easily
integrate other PHP standard libraries and frameworks with li₃ applications,
and vice-versa.

li₃ integrates the latest storage technologies, including MongoDB, CouchDB and
Redis, with plugin support for Cassandra, ElasticSearch and others.

### Flexibility

li₃ gives you full control over your application, from filters to dynamically
modify framework internals, to dynamic dependencies to extend and replace core
classes with application or plugin classes, to heavy use of adapter-oriented
configurations, to make it seamless to move between different technologies and
options.

Every component of the li₃ framework stack is replaceable through the
robust plugin architecture. Swap out the default ORM / ODM implementation
for [Doctrine 2](https://github.com/mariano/li3_doctrine2/) or [PHP
ActiveRecord](https://github.com/greut/li3_activerecord). Don't like
the templating? Use [Twig](https://github.com/UnionOfRAD/li3_twig),
[Mustache](https://github.com/bruensicke/li3_mustache), or roll your own.

If you don't even need to write a full application, build a micro-app in a
single file using the routing system, without giving up the maintainability of
the framework's structure.

