<div class="test-result test-result-<?= ($success ? 'success' : 'fail') ?>">
<?= $count['passes'] ?> / <?= $count['asserts'] ?> passes, <?= $count['fails'] ?>
<?= ((intval($count['fails']) == 1) ? ' fail' : ' fails') ?> and <?= $count['exceptions'] ?>
<?= ((intval($count['exceptions']) == 1) ? ' exception' : ' exceptions') ?>
</div>

<?php foreach ((array) $stats['errors'] as $error): ?>
	<?php if ($error['result'] == 'fail'): ?>
		<div class="test-assert test-assert-failed">
			Assertion '<?= $error['assertion'] ?>' failed in
			<?= $error['class'] ?>::<?= $error['method']?>() on line
			<?= $error['line'] ?>:
			<span class="content"><?= $error['message'] ?></span>
		</div>
	<?php elseif ($error['result'] == 'exception'): ?>
		<div class="test-exception">
			Exception thrown in <?= $error['class'] ?>::<?= $error['method'] ?>()
			on line <?= $error['line'] ?>:
			<span class="content"><?= $error['message'] ?></span>
			<?php if (isset($error['trace']) && !empty($error['trace'])): ?>
				Trace: <span class="trace"><?= $error['trace'] ?></span>
			<?php endif ?>
		</div>
	<?php endif ?>
<?php endforeach ?>

<?php foreach ((array) $stats['skips'] as $skip): ?>
	<div class="test-skip">
		Skip <?= $skip['trace'][1]['class'] ?>::<?= $skip['trace'][1]['function'] ?>()
		on line <?= $skip['trace'][1]['line'] ?>:
		<span class="content"><?= $skip['message'] ?></span>
	</div>
<?php endforeach ?>