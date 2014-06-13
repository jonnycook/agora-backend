<?php

require_once('includes/header.php');

echo '<h2>Extension Errors</h2>';
$result = mysqli_query($mysqli, "SELECT DISTINCT args FROM extension_errors WHERE !archived ORDER BY `timestamp` DESC");// or die(mysqli_error($mysqli));
while ($row = mysqli_fetch_assoc($result)) {
	echo $row['args'], '<br>';
}
echo '<h3>Users</h3>';
$result = mysqli_query($mysqli, "SELECT DISTINCT userId FROM extension_errors WHERE !archived");// or die(mysqli_error($mysqli));
while ($row = mysqli_fetch_assoc($result)) {
	echo $row['userId'], '<br>';
}

echo '<h2>Update Errors</h2>';
$result = mysqli_query($mysqli, "SELECT DISTINCT error_string, request_extVersion FROM update_errors ORDER BY `timestamp` DESC");// or die(mysqli_error($mysqli));
while ($row = mysqli_fetch_assoc($result)) {
	echo "$row[request_extVersion]: $row[error_string]<br>";
}


// SELECT DISTINCT request_userId FROM update_logs WHERE request_changes != '{}' ORDER BY request_userId DESC