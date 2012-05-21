<?php

$passes = intval($count['passes']) ?: 0;
$asserts = intval($count['asserts']) ?: 0;
$fails = intval($count['fails']) ?: 0;
$exceptions = intval($count['exceptions']) ?: 0;

if ($success) {
	echo "{:green}OK{:end}\n";
} else {
	echo "{:red}FAIL{:end}\n";
}
echo "\n";

echo "{$passes} / {$asserts} passes\n";
echo "{$fails} " . ($fails == 1 ? 'fail' : 'fails');
echo " and {$exceptions} ";
echo ($exceptions == 1 ? 'exception' : 'exceptions') . "\n";

foreach ((array) $stats['errors'] as $error) {
	if ($error['result'] == 'fail') {
		echo "\nAssertion `{$error['assertion']}` failed in ";
		echo "`{$error['class']}::{$error['method']}()` on line ";
		echo "{$error['line']}:\n{$error['message']}";
	} elseif ($error['result'] == 'exception') {
		echo "Exception thrown in `{$error['class']}::{$error['method']}()` ";
		echo "on line {$error['line']}:\n{$error['message']}";
		if (isset($error['trace']) && !empty($error['trace'])) {
			echo "Trace: {$error['trace']}\n";
		}
	}
}
foreach ((array) $stats['skips'] as $skip) {
	$trace = $skip['trace'][1];
	echo "Skip `{$trace['class']}::{$trace['function']}()` ";
	echo "on line {$trace['line']}:\n";
	echo "{$skip['message']}\n";
}

?>