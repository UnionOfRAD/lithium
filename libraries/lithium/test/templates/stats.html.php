<div class="test-result test-result-<?php echo ($success ? 'success' : 'fail') ?>">
<?php echo $count['passes'] ?> / <?php echo $count['asserts'] ?> passes, <?php echo $count['fails'] ?>
<?php echo ((intval($count['fails']) == 1) ? ' fail' : ' fails') ?> and <?php echo $count['exceptions'] ?>
<?php echo ((intval($count['exceptions']) == 1) ? ' exception' : ' exceptions') ?>
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
			Exception thrown in <?php echo $error['class'] ?>::<?php echo $error['method'] ?>()
			on line <?php echo $error['line'] ?>:
			<span class="content"><?php echo $error['message'] ?></span>
			<?php if (isset($error['trace']) && !empty($error['trace'])): ?>
				Trace: <span class="trace"><?php echo $error['trace'] ?></span>
			<?php endif ?>
		</div>
	<?php endif ?>
<?php endforeach ?>

<?php foreach ((array) $stats['skips'] as $skip): ?>
	<div class="test-skip">
		Skip <?php echo $skip['trace'][1]['class'] ?>::<?php echo $skip['trace'][1]['function'] ?>()
		on line <?php echo $skip['trace'][1]['line'] ?>:
		<span class="content"><?php echo $skip['message'] ?></span>
	</div>
<?php endforeach ?>