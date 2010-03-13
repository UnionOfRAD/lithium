<?php

$passes = intval($count['passes']) ?: 0;
$asserts = intval($count['asserts']) ?: 0;
$fails = intval($count['fails']) ?: 0;
$exceptions = intval($count['exceptions']) ?: 0;

echo "\n" . ($success ? '{:success}' : '') . "{$passes} / {$asserts} passes\n";
echo "{$fails} " . ($fails == 1 ? 'fail' : 'fails');
echo " and {$exceptions} ";
echo ($exceptions == 1 ? 'exception' : 'exceptions') . ($success ? '{:end}' : '') . "\n";

foreach ((array) $stats['errors'] as $error) {
	if ($error['result'] == 'fail') {
		echo "\n{:error}Assertion '{$error['assertion']}' failed in ";
		echo "{$error['class']}::{$error['method']}() on line ";
		echo "{$error['line']}:{:end} \n{$error['message']}";
	} elseif ($error['result'] == 'exception') {
		echo "{:error}Exception thrown in {$error['class']}::{$error['method']}()";
		echo " on line {$error['line']}:{:end} \n{$error['message']}";
		if (isset($error['trace']) && !empty($error['trace'])) {
			echo "Trace: {$error['trace']}\n";
		}
	}
}
foreach ((array) $stats['skips'] as $skip) {
	$trace = $skip['trace'][1];
	echo "Skip {$trace['class']}::{$trace['function']}() on line {$trace['line']}:";
	echo "{$skip['message']}\n";
}

?>