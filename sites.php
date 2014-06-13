<?php

require_once('includes/header.php');

$result = mysqli_query($mysqli, "SELECT * FROM sites ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($result)) {
	echo "<a href='siteManager/edit.php?name=$row[name]'>$row[name]</a><br>";
}