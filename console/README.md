# console

The console package contains a set of classes required to route and dispatch
incoming console requests. Moreover it contains the console front-controller
file (`lithium.php`) as well as wrappers for both *nix and Windows environments
(`li3` and `li3.bat` respectively), allowing to easily invoke the
console front-controller from the command line.

A command is to the command line what an action controller is to the HTTP
request/response flow. In that commands are quite similar to controllers.
Commands don't leverage the full MVC as they don't utilize views, but
directly interact with the user through `in()` and `out()`.

li3 itself provides amongst others commands for creating new applications
or parts thereof. However commands can also be provided through other libraries
or by your application. Commands running in the application context will have
complete access to your application. This is especially useful to reuse
existing logic in an application's model when creating a command to be run as
i.e. a cron-job.

## Invoking the front-controller

You invoke the console front-controller through one of the wrappers provided, as shown below.
The examples shown are relative to the root directory of a standard li3 distribution. The first
is for users on a *nix command line the second for users on a Windows system.

```sh
libraries/lithium/console/li3
libraries/lithium/console/li3.bat
```

Invoking the wrapper like that (without arguments) should give you a
list of available commands.

## Built-in commands

Using the commands which come with lithium is easy. Invoke the wrapper without
any arguments to get a list of all available commands. Get a description about
each command and the options and arguments it accepts or may require by using
the `help` command.

```sh
li3 help
li3 help create
li3 help g11n
```

## Creating custom commands

Creating your own commands is very easy. A few fundamentals:

- All commands inherit from `lithium\console\Command`.
- Commands are normally placed in your application or library's `extensions/command` directory.

Here's an example command:

```php
namespace app\extensions\command;

class HelloWorld extends \lithium\console\Command {

	public function run() {
		$this->header('Welcome to the Hello World command!');
		$this->out('Hello, World!');
	}
}
```

If you would like to try this command, create an application or use an existing
application, place the command into the application's `extensions/commands`
directory and save it as `HelloWorld.php`. After doing so open a shell and
change directory to your application's directory and run the following command:

```sh
li3 hello_world
```

Although it's probably obvious, when this command runs it will output a nice
header with the text `Welcome to the Hello World command!` and some regular
text `Hello, World!` after it.

The public method `run()` is called on your command instance every time your
command has been requested to run. From this method you can add your own command
logic.

### Parsing options and arguments

Parsing options and arguments to commands should be simple. In fact, the
parsing is already done for you.

Short and long (GNU-style) options in the form of `-f`, `--foo`, `--foo-bar` and `--foo=bar`
are automatically parsed and exposed to your command instance through its
properties. XF68-style long options (i.e. `-foo`) are not supported by default
but support can be added by extending the console router.

Arguments are passed directly to the invoked method.

Let's look at an example, going back to the `hello_world` command from earlier:

```php
namespace app\extensions\command;

class HelloWorld extends \lithium\console\Command {

	public $recipient;

	public function run() {
		$this->header('Welcome to the Hello World command!');
		$this->out('Hello, ' . ($this->recipient ?: 'World') . '!');
	}
}
```

Notice the additional property `$recipient`? Great! Now when `--recipient` is
passed to the `hello_world` command, the recipient property on your command
instance will be set to whatever was passed into the command at runtime.

Try it out with the following command:

```sh
li3 hello_world --recipient=AwesomeGuy
```

You should get a special greeting from our good old `hello_world` command.
