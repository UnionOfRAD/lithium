<?php
	use \lithium\util\Inflector;
	$base = $request->env('base');
?>
<!doctype html>
<html>
	<head>
		<title></title>
		<link rel="stylesheet" href="<?php echo $base;?>/css/debug.css" />
		<link href="<?php echo $base;?>/favicon.ico" title="Icon" type="image/x-icon" rel="icon" />
		<link href="<?php echo $base;?>/favicon.ico" title="Icon" type="image/x-icon" rel="shortcut icon" /></head>
	</head>
	<body class="test-dashboard">
		<h1>Lithium Unit Test Dashboard</h1>

		<div style="float: left; padding: 10px 0 20px 20px; width: 20%;">
			<h2>Select Test(s):</h2>
			<a class="test-button" href="<?= $base ?>/test/lithium/tests">Run All Tests</a>
			<?= $report->render("menu", array("menu" => $menu, "base" => $base)) ?>
		</div>

		<div style="float:left; padding: 10px; width: 75%">
			<h2>Stats for <?php echo $report->title; ?></h2>

			<h3>Test results</h3>

			<span class="filters">
				<?php echo join(' | ', array_map(
					function($class) use ($request) {
						$url = "?filters[]={$class}";
						$name = join('', array_slice(explode("\\", $class), -1));
						$key = Inflector::underscore($name);
						return "<a class=\"{$key}\" href=\"{$url}\">{$name}</a>";
					},
					$filters
				)); ?>
			</span>
			<?= $report->render("stats", $report->stats()) ?>
			<?php foreach ($report->results['filters'] as $filter => $data): ?>
				<?= $report->render(
						strtolower(array_pop(explode("\\", $filter))),
						array("analysis" => $data)
					); ?>
			<?php endforeach ?>
		</div>
		<div style="clear:both"></div>
	</body>
</html>