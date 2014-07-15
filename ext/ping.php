<?php

require_once('includes/header.php');

$id = mysqli_real_escape_string($mysqli, $_GET['id']);
$version = mysqli_real_escape_string($mysqli, $_GET['version']);
$userId = userId();

$response = mysqli_query($mysqli, "SELECT id,command FROM client_commands WHERE stage = 0 && client_id = '$id'");
while ($row = mysqli_fetch_assoc($response)) {
	$commands[] = array(
		'id' => $row['id'],
		'command' => $row['command'],
	);
}
if ($commands) {
	mysqli_query($mysqli, "UPDATE client_commands SET stage = 1 WHERE id IN (" . implode(',', array_map(function($i) { return $i['id']; }, $commands))  . ')');
}


$extension = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM extension_instances WHERE id = '$id'"));
if ($extension) {
	$sql = array(
		'last_pinged_at = UTC_TIMESTAMP()',
	);
	if ($extension['version'] != $version) {
		$sql[] = "current_version = '$version'";
	}
	if ($extension['user_id'] != $userId) {
		$sql[] = "user_id = $userId";
		if (!$extension['user_id']) {
			$sql[] = 'first_user_at = UTC_TIMESTAMP()';
		}
	}

	mysqli_query($mysqli, "UPDATE extension_instances SET " . implode(',', $sql) . " WHERE id = '$id'");
}
else {
	$sql = array(
		"id = '$id'",
		'first_pinged_at = UTC_TIMESTAMP()',
		'last_pinged_at = UTC_TIMESTAMP()',
		"installed_version = '$version'",
		"current_version = '$version'",
	);

	if ($userId) {
		$sql[] = "user_id = $userId";
		$sql[] = "first_user_at = UTC_TIMESTAMP()";
	}

	mysqli_query($mysqli, "INSERT INTO extension_instances SET " . implode(',', $sql));
}

if ($commands) {
	echo json_encode(array(
		'commands' => $commands
	));
}