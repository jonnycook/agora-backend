<?php

require_once('includes/header.php');


function insert($values, $table) {
	global $mysqli;
	foreach ($values as $field => $value) {
		$setters[] = "`$field` = '" . mysqli_real_escape_string($mysqli, $value) . '\'';
	}

	$query = "INSERT INTO `$table` SET " . implode(',', $setters);
	mysqli_query($mysqli, $query) or die("$query: " . mysqli_error($mysqli));
}

insert(array(
	'userId' => $_POST['userId'],
	'error' => json_encode($_POST['error']),
	'args' => json_encode($_POST['args']),
	'extVersion' => $_POST['extVersion'],
	'apiVersion' => $_POST['apiVersion'],
	'instanceId' => $_POST['instanceId'],
	'clientId' => $_POST['clientId'],
	'ip' => remoteAddr()
), 'extension_errors');
