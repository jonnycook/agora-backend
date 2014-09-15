<?php

require_once('../includes/header.php');

$name = mysqli_real_escape_string($mysqli, $_GET['name']);
$site = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM sites WHERE name = '$name'"));

if ($_POST['conf']) {
	$conf = mysqli_real_escape_string($mysqli, $_POST['conf']);
	mysqli_query($mysqli, "UPDATE sites SET conf = '$conf' WHERE id = $site[id]");
}

$site = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM sites WHERE name = '$name'"));


?>

<link rel="stylesheet" href="codemirror/lib/codemirror.css">
<link rel="stylesheet" href="codemirror/addon/fold/foldgutter.css">
<link rel="stylesheet" href="codemirror/addon/dialog/dialog.css">
<link rel="stylesheet" href="codemirror/theme/monokai.css">
<script src="codemirror/lib/codemirror.js"></script>
<script src="codemirror/addon/search/searchcursor.js"></script>
<script src="codemirror/addon/search/search.js"></script>
<script src="codemirror/addon/dialog/dialog.js"></script>
<script src="codemirror/addon/edit/matchbrackets.js"></script>
<script src="codemirror/addon/edit/closebrackets.js"></script>
<script src="codemirror/addon/comment/comment.js"></script>
<script src="codemirror/addon/wrap/hardwrap.js"></script>
<script src="codemirror/addon/fold/foldcode.js"></script>
<script src="codemirror/addon/fold/brace-fold.js"></script>
<script src="codemirror/mode/javascript/javascript.js"></script>
<script src="codemirror/keymap/sublime.js"></script>

<form method="post">
	<ul>
		<li><textarea id="code" name="conf"><?php echo $site['conf'] ?></textarea></li>
	</ul>
	<input type="submit">
</form>

<script type="text/javascript">
	CodeMirror.fromTextArea(document.getElementById('code'), {
		lineNumbers: true,
		mode: "application/ld+json",
		keyMap: "sublime",
		autoCloseBrackets: true,
		matchBrackets: true,
		showCursorWhenSelecting: true,
		theme: "monokai",
		lineWrapping: true,
		indentWithTabs: true,
		tabSize: 2
	});
</script>