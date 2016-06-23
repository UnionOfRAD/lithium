{:heading}Code Coverage{:end}
<?php

	$summary = [
		'classes' => 0, 'executable' => 0, 'covered' => 0, 'uncovered' => 0, 'percentage' => 0
	];

	$colorMap = [
		'ignored' => 'white',
		'covered' => 'success',
		'uncovered' => 'error',
	];

	foreach ($data as $class => $coverage) {
		$summary['classes']++;
		$summary['executable'] += count($coverage['executable']);
		$summary['covered'] += count($coverage['covered']);
		$summary['uncovered'] += count($coverage['uncovered']);
		$summary['percentage'] += $coverage['percentage'];

		echo ($coverage['percentage'] >= 85 ? "{:success}" : "{:error}");
		echo "{$class}{:end}: ";
		echo count($coverage['covered']) . " of " . count($coverage['executable']);
		echo " lines covered (";
		echo ($coverage['percentage'] >= 85 ? "{:success}" : "{:error}");
		echo "{$coverage['percentage']}%{:end})\n";

		if ($coverage['percentage'] == 100) {
			continue;
		}
		echo "\n{:heading}Coverage analysis{:end}\n";

		foreach ($coverage['output'] as $file => $lines) {
			echo "\n{$file}:\n";

			foreach ($lines as $num => $line) {
				$color = $colorMap[$line['class']];
				echo "{:{$color}}{$num} {$line['data']}{:end}\n";
			}
		}
		echo "\n";
	}

$displayPercentage = function($raw) {
    $percentage = round($raw);
    echo ($percentage > 70) ? "{:success}" : "{:error}";
    echo "$percentage{:end}";
};
?>

{:heading}Summary{:end}

Classes Covered:   <?php echo $summary['classes'] ?>

Executable Lines:  <?php echo $summary['executable'] ?>

Lines Covered:     <?php echo $summary['covered'] ?>

Lines Uncovered:   <?php echo $summary['uncovered'] ?>

Total Coverage:    <?php
	$percTotal = ($summary['covered'] / $summary['executable']) * 100;
	$displayPercentage($percTotal);
	?>%
Average Per Class: <?php
	$percPerClass = $summary['percentage'] / $summary['classes'];
	$displayPercentage($percPerClass);
	?>%
