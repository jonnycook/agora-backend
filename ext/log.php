<?php

require_once('includes/header.php');

function insert($values, $table) {
	global $mysqli;
	foreach ($values as $field => $value) {
		if ($value === null) {
			$setters[] = "`$field` = NULL";
		}
		else {
			$setters[] = "`$field` = '" . mysqli_real_escape_string($mysqli, $value) . '\'';
		}
	}

	$query = "INSERT INTO `$table` SET " . implode(',', $setters);
	mysqli_query($mysqli, $query) or die("$query: " . mysqli_error($mysqli));
}

insert(array(
	'args' => $_POST['args'],
	'userId' => $_POST['userId'],	
	'extVersion' => $_POST['extVersion'],
	'clientId' => $_POST['clientId'],
	'instanceId' => $_POST['instanceId'],
	'timestamp' => $_POST['timestamp'],
), 'extension_logs');
