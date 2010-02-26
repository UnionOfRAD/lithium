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
		<div id="header">
			<header>
				<h1><a href="<?php echo $base ?>/test/">Lithium Unit Test Dashboard</a></h1>
				<a class="test-button" href="<?php echo $base ?>/test/all">Run All Tests</a>
			</header>
		</div>

		<div class="article">
				<article>
					<div class="test-menu">
						<h2>Select Test(s):</h2>
						<?php echo $report->render("menu", array("menu" => $menu, "base" => $base)) ?>
					</div>

					<div class="test-content">
						<h2>Stats for <?php echo $report->title; ?></h2>

						<h3>Test results</h3>

						<span class="filters">
							<?php echo join('', array_map(
								function($class) use ($request) {
									$url = "?filters[]={$class}";
									$name = join('', array_slice(explode("\\", $class), -1));
									$key = Inflector::underscore($name);
									return "<a class=\"{$key}\" href=\"{$url}\">{$name}</a>";
								},
								$filters
							)); ?>
						</span>
						<?php echo $report->render("stats", $report->stats()) ?>
						<?php foreach ($report->results['filters'] as $filter => $data): ?>
							<?php
								$filterClass = explode("\\", $filter);
								$filterClass = array_pop($filterClass);
								echo $report->render(
									strtolower($filterClass),
									array("analysis" => $data)
								); ?>
						<?php endforeach ?>
					</div>
			</article>
		</div>
		<div style="clear:both"></div>
	</body>
</html>