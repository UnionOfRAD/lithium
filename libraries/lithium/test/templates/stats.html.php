<div class="test-result test-result-<?php ($success ? 'success' : 'fail') ?>">
<?php $count['passes'] ?> / <?php $count['asserts'] ?> passes, <?php $count['fails'] ?>
<?php ((intval($count['fails']) == 1) ? ' fail' : ' fails') ?> and <?php $count['exceptions'] ?>
<?php ((intval($count['exceptions']) == 1) ? ' exception' : ' exceptions') ?>
</div>

<?php foreach ((array) $stats['errors'] as $error): ?>
	<?php if ($error['result'] == 'fail'): ?>
		<div class="test-assert test-assert-failed">
			Assertion '<?php $error['assertion'] ?>' failed in
			<?php $error['class'] ?>::<?php $error['method']?>() on line
			<?php $error['line'] ?>:
			<span class="content"><?php $error['message'] ?></span>
		</div>
	<?php elseif ($error['result'] == 'exception'): ?>
		<div class="test-exception">
			Exception thrown in <?php $error['class'] ?>::<?php $error['method'] ?>()
			on line <?php $error['line'] ?>:
			<span class="content"><?php $error['message'] ?></span>
			<?php if (isset($error['trace']) && !empty($error['trace'])): ?>
				Trace: <span class="trace"><?php $error['trace'] ?></span>
			<?php endif ?>
		</div>
	<?php endif ?>
<?php endforeach ?>

<?php foreach ((array) $stats['skips'] as $skip): ?>
	<div class="test-skip">
		Skip <?php $skip['trace'][1]['class'] ?>::<?php $skip['trace'][1]['function'] ?>()
		on line <?php $skip['trace'][1]['line'] ?>:
		<span class="content"><?php $skip['message'] ?></span>
	</div>
<?php endforeach ?>