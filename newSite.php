<?php

require_once('includes/header.php');

if ($_POST['name']) {
	$name = mysqli_real_escape_string($mysqli, $_POST['name']);
	mysqli_query($mysqli, "INSERT INTO sites SET name = '$name', created_at = UTC_TIMESTAMP()");
	// $id = mysqli_insert_id($mysqli);
	file_put_contents(SHARED_PATH . "$_POST[name].php", $_POST['code']);
}

?>

<form method="post">
	<ul>
		<li><input type="text" name="name" placeholder="Name"></li>
		<li><textarea name="code"></textarea></li>
	</ul>
	<input type="submit">
</form>