<?php
/**
 * Lithium: the most rad php framework
 * Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 *
 * Licensed under The BSD License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009, Union of Rad, Inc. (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use \lithium\core\Libraries;
use \lithium\test\Group;
use \lithium\test\Dispatcher;
use \lithium\util\Inflector;
use \lithium\util\reflection\Inspector;

$startBenchmark = microtime(true);

error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

require dirname(__DIR__) . '/config/bootstrap.php';
$core = dirname(dirname(__DIR__)) . '/libraries/lithium';

$testRun = Dispatcher::run(null, $_GET);
$stats = Dispatcher::process($testRun['results']);

?>
<!doctype html>
<html>
	<head>
		<title>Lithium Unit Test Dashboard</title>
		<link rel="stylesheet" href="css/debug.css" />
	</head>
	<body class="test-dashboard">
		<h1>Lithium Unit Test Dashboard</h1>

		<div style="float: left; padding: 10px 0 20px 20px; width: 20%;">
			<h2><a href="?group=\">Tests</a></h2>
			<?php echo Dispatcher::menu('html'); ?>
		</div>

		<div style="float:left; padding: 10px; width: 75%">
			<h2>Stats for <?php echo $testRun['title']; ?></h2>

			<h3>Test results</h3>

			<span class="filters">
				<?php
					$filters = Libraries::locate('testFilters');
					$base = $_SERVER['REQUEST_URI'];

					foreach ($filters as $i => $class) {
						$url = $base . "&amp;filters[]={$class}";
						$name = join('', array_slice(explode("\\", $class), -1));
						$key = Inflector::underscore($name);

						echo "<a class=\"{$key}\" href=\"{$url}\">{$name}</a>";

						if ($i < count($filters) - 1) {
							echo ' | ';
						}
					}
				?>
			</span>

			<?php
				$passes = count($stats['passes']);
				$fails = count($stats['fails']);
				$errors = count($stats['errors']);
				$exceptions = count($stats['exceptions']);
				$success = ($passes === $stats['asserts'] && $errors === 0);

				echo '<div class="test-result test-result-' . ($success ? 'success' : 'fail') . '"';
				echo ">{$passes} / {$stats['asserts']} passes, {$fails} ";
				echo ((intval($stats['fails']) == 1) ? 'fail' : 'fails') . " and {$exceptions} ";
				echo ((intval($exceptions) == 1) ? 'exceptions' : 'exceptions');
				echo '</div>';

				foreach ((array)$stats['errors'] as $error) {
					switch ($error['result']) {
						case 'fail':
							$error += array('class' => 'unknown', 'method' => 'unknown');
							echo '<div class="test-assert test-assert-failed">';
							echo "Assertion '{$error['assertion']}' failed in ";
							echo "{$error['class']}::{$error['method']}() on line ";
							echo "{$error['line']}: ";
							echo "<span class=\"content\">{$error['message']}</span>";
						break;
						case 'exception':
							echo '<div class="test-exception">';
							echo "Exception thrown in  {$error['class']}::{$error['method']}() ";
							echo "on line {$error['line']}: ";
							echo "<span class=\"content\">{$error['message']}</span>";
							if (isset($error['trace']) && !empty($error['trace'])) {
								echo "Trace:<span class=\"trace\">{$error['trace']}</span>";
							}
						break;
					}
					echo '</div>';
				}

				foreach ((array)$testRun['filters'] as $class => $data) {
					echo $class::output('html', $data);
				}

				$tests = Group::all(array('transform' => true));
				$exclude = '/\w+Test$|webroot|index$|^app\\\\config|^\w+\\\\views\/|\./';
				$options = compact('exclude') + array('recursive' => true);
				$classes = array_diff(Libraries::find('lithium', $options), $tests);
				sort($classes);
			?>
			<h3>Classes with no test case (<?php echo count($classes); ?>)</h3>
			<ul class="classes">
			<?php
				foreach ($classes as $class) {
					echo "<li>{$class}</li>";
				}
			?>
			</ul>

			<h3>Included files (<?php echo count(get_included_files()); ?>)</h3>
			<ul class="files">
				<?php
					$base = dirname(dirname($core));
					$files = str_replace($base, '', get_included_files());
					sort($files);

					foreach ($files as $file) {
						echo "<li>{$file}</li>";
					}
				?>
			</ul>
		</div>
		<div style="clear:both"></div>
	</body>
</html>