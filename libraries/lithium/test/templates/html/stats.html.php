<?php

$passes = intval($count['passes']) ?: 0;
$asserts = intval($count['asserts']) ?: 0;
$fails = intval($count['fails']) ?: 0;
$exceptions = intval($count['exceptions']) ?: 0;

?>
<div class="test-result test-result-<?php echo ($success ? 'success' : 'fail') ?>">
	<?php echo "{$passes} / {$asserts} passes, {$fails} " . ($fails == 1 ? ' fail' : ' fails'); ?>
	and <?php echo $exceptions ?> <?php echo ($exceptions == 1 ? ' exception' : ' exceptions') ?>
</div>

<?php foreach ((array) $stats['errors'] as $error): ?>
	<?php if ($error['result'] == 'fail'): ?>
		<div class="test-assert test-assert-failed">
			Assertion '<?php echo $error['assertion'] ?>' failed in
			<?php echo $error['class'] ?>::<?php echo $error['method']?>() on line
			<?php echo $error['line'] ?>:
			<span class="content"><?php echo $error['message'] ?></span>
		</div>
	<?php elseif ($error['result'] == 'exception'): ?>
		<div class="test-exception">
			Exception thrown in <?php echo "{$error['class']}::{$error['method']}()"; ?>
			on line <?php echo $error['line'] ?>:
			<span class="content"><?php echo $error['message'] ?></span>
			<?php if (isset($error['trace']) && !empty($error['trace'])): ?>
				Trace: <span class="trace"><?php echo $error['trace'] ?></span>
			<?php endif ?>
		</div>
	<?php endif ?>
<?php endforeach ?>

<?php foreach ((array) $stats['skips'] as $skip): ?>
	<?php $trace = $skip['trace'][1]; ?>
	<div class="test-skip">
		<?php $method = $trace['function']; ?>
		<?php $test = $trace['class'] . ($method != 'skip' ? "::{$method}()" : ''); ?>
		Skipped test <?php echo $test ?>
		<span class="content"><?php echo $skip['message'] ?></span>
	</div>
<?php endforeach ?>