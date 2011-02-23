<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2011, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

use lithium\analysis\Debugger;
use lithium\analysis\Inspector;

$exception = $info['exception'];

/**
 * Set Lithium-esque colors for syntax highlighing.
 * Do these belong here? Maybe they should move to the layout or error bootstrap?
 */
ini_set('highlight.string', '#4DDB4A');
ini_set('highlight.comment', '#D42AAE');
ini_set('highlight.keyword', '#D42AAE');
ini_set('highlight.default', '#3C96FF');
ini_set('highlight.htm', '#FFFFFF');

/**
 * Helper function for generating line numbered code output with syntax highlighting.
 *
 * Should this function belong in a helper or something?
 *
 * @see lithium\analysis\Inspector::lines()
 * @param string $file Normally a full path to a specific file
 * @param integer $line The central line number to retrieve
 */
function renderCodeExcerpt($file = null, $line = null) {

	$sourceCode = Inspector::lines($file, range($line - 3, $line + 3));

	?>

	<div class="lithium-exception-location">
		<?php echo "{$file}: {$line}"; ?>
	</div>

	<div class="lithium-code-dump">

		<?php
		/**
		 * We can get rid of the 'code' HTML elements if we prefer a lighter background
		 * color. Also, it bothers me that the syntax highlighting isn't an exact match
		 * with li3_docs. The highlight color can make the code a little hard to read.
		 * If we stick with the dark background color, we might want to adjust the
		 * highlight color.
		 */
		?>

		<pre><code><?php

			foreach ($sourceCode as $num => $content) :

				// Pad the line number.
				// We shouldn't have any files longer than 999 lines, right? ;)
				$numPad = str_pad($num, 3, ' ');

				// Nuke any existing PHP start or end tags
				$content = str_ireplace(array('<?php', '?>'), '', $content);

				// Add the line number and wrap in PHP start and end tags
				$content = "<?php {$numPad}{$content} ?>";

				// Apply syntax highlighting
				$content = highlight_string($content, true);

				// Clean out PHP start and end tags, 'code' elements, and newlines
				$content = str_replace(array('&lt;?php', '?&gt;', '<code>', '</code>', "\n"), '', $content);

				if ($line === $num):
					?><span class="code-highlight"><?php
				endif;?><?php echo "{$content}\n"; ?><?php

				if ($line === $num):
					?></span><?php
				endif;

			endforeach;

		?></code></pre>

	</div><?php

}

?>

<?php
/**
 * It would be cool if the name of the exception was a link to the API.
 * Also, should we show the exception code? That might be helpful for HTTP exceptions.
 */
?>
<h3>Exception</h3>
<div class="lithium-exception-class">
	<?=get_class($exception);?>

	<?php if ($code = $exception->getCode()): ?>
		<span class="code">(code <?=$code; ?>)</span>
	<?php endif ?>
</div>
<div class="lithium-exception-message"><?=$exception->getMessage();?></div>

<h3 id="source">Source</h3>
<div id="sourceCode">
	<?php renderCodeExcerpt($exception->getFile(), $exception->getLine()); ?>
</div>

<?php
/**
 * In the future, maybe we'll add a 'Show All' link or button. How would that work?
 * Also we plan to improve the way that closures are displayed in the stack trace.
 */
?>
<h3>Stack Trace</h3>
<div class="lithium-stack-trace">
	<?php // Should this list be zero based? ?>
	<ol>
		<li>
			<?php // TODO: What should this link title be? ?>
			<tt><a href="#source" id="0" class="display-source-excerpt">[Exception Thrown]</a></tt>
			<div id="sourceCode0" style="display: none;"></div>
		</li>
		<?php foreach (Debugger::trace(array('format' => 'array', 'trace' => $exception->getTrace())) as $id => $frame) : ?>
			<li>
				<tt><a href="#source" id="<?=$id + 1;?>" class="display-source-excerpt"><?=$frame['functionRef'];?></a></tt>
				<div id="sourceCode<?=$id + 1;?>" style="display: none;">
					<?php renderCodeExcerpt($frame['file'], $frame['line']); ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</div>

<script type="text/javascript">
	window.onload = function() {
		// Copy the original source excerpt so we can re-display it when neccesary
		var content = document.getElementById('sourceCode').innerHTML;
		document.getElementById('sourceCode0').innerHTML = content;

		// Apply click event handlers
		var links = document.getElementsByTagName('a');
		for (i = 0; i < links.length; i++) {
			// Only apply this to links with the 'display-source-excerpt' class
			if (links[i].className.indexOf('display-source-excerpt') >= 0) {
				links[i].onclick = function() {
					var elementId = 'sourceCode' + this.id;
					var content = document.getElementById(elementId).innerHTML;
					document.getElementById('sourceCode').innerHTML = content;
				}
			}
		}
	}
</script>