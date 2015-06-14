<div class="test-exception">
	<strong><?php echo $error['name'] ?></strong>
	<?php if ($error['code'] !== 0): ?>
		(<?php echo $error['code'] ?>)
	<?php endif ?>
	thrown
	in <strong><?php echo "{$error['class']}::{$error['method']}()"; ?></strong>
	on line <?php echo $error['line'] ?>
	<span class="content"><?php echo $error['message'] ?></span>
	<?php if (isset($error['trace']) && !empty($error['trace'])): ?>
		Trace
		<span class="trace"><?php echo $error['trace'] ?></span>
	<?php endif ?>
</div>