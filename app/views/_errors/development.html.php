<?php
/**
 * It would be cool if the name of the exception was a link to the API.
 * Also, should we show the exception code? That might be helpful for HTTP exceptions.
 */
use lithium\core\ErrorHandler;
use lithium\analysis\Inspector;

$exception = $info['exception'];
$line = $exception->getLine();

$sourceCode = Inspector::lines($exception->getFile(), range($line - 3, $line + 3));
$stackTrace = ErrorHandler::trace($exception->getTrace());

?>
<h3>Exception</h3>
<div class="lithium-exception-class"><?=get_class($exception);?></div>
<div class="lithium-exception-message"><?=$exception->getMessage();?></div>

<h3>Source</h3>
<div class="lithium-exception-location">
	<?=$exception->getFile(); ?>: <?=$exception->getLine(); ?>
</div>
<div class="lithium-code-dump">
	<pre><?php foreach ($sourceCode as $num => $content):
		$numPad = str_pad($num, 3, ' ');

		if ($line === $num):
			?><span class="code-highlight"><?php
		endif;?><?="{$numPad} {$content}\n"; ?><?php

		if ($line === $num):
			?></span><?php
		endif;

	endforeach; ?></pre>
</div>

<?php
/**
 * I think it would be cool if each row of the stack trace could be clicked on
 * to expand a div with the source code for that item. But I wasn't sure if we
 * wanted to introduce a JavaScript dependency.
 */
?>
<h3>Stack Trace</h3>
<div class="lithium-stack-trace">
	<ol>
		<?php foreach ($stackTrace as $trace) : ?>
			<li><tt><?=$trace; ?>()</tt></li>
		<?php endforeach; ?>
	</ol>
</div>