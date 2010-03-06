<?php
	echo "===Benchmarks===\n";

	foreach ($analysis['totals'] as $title => $data) {
		echo "{$title}: {$data['formatter']($data['value'])}\n";
	}
?>