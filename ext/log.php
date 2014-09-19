<?php

require_once('includes/header.php');

header('Access-Control-Allow-Origin: http://webapp.agora.dev');
header('Access-Control-Allow-Origin: http://agora.sh');
header('Access-Control-Allow-Credentials: true');

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
