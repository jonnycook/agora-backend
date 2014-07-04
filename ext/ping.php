<?php

require_once('includes/header.php');

$id = mysqli_real_escape_string($mysqli, $_GET['id']);
$version = mysqli_real_escape_string($mysqli, $_GET['version']);
$userId = userId();

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