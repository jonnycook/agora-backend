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
	'type' => $_POST['type'],
	'error_message' => $_POST['error']['message'],
	'error_stack' => $_POST['error']['stack'],
	'error_line' => $_POST['error']['line'],
	'error_column' => $_POST['error']['column'],
	'error_file' => $_POST['error']['file'],
	'error_info' => json_encode($_POST['error']['info']),

	'userId' => $_POST['userId'],	
	'args' => json_encode($_POST['args']),
	'extVersion' => $_POST['extVersion'],
	'apiVersion' => $_POST['apiVersion'],
	'clientId' => $_POST['clientId'],
	'ip' => remoteAddr()
), 'extension_errors');
