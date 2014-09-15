<?php

require_once('includes/header.php');

$data = mysqli_real_escape_string($mysqli, $_POST['data']);

$site = mysqli_real_escape_string($mysqli, $_POST['site']);
$sid = mysqli_real_escape_string($mysqli, $_POST['sid']);

mysqli_query($mysqli, "UPDATE test_products SET data = '$data', updated_at = UTC_TIMESTAMP() WHERE site = '$site' && sid = '$sid'");

if (!mysqli_affected_rows($mysqli)) {
	mysqli_query($mysqli, "INSERT INTO test_products SET data = '$data', site = '$site', sid = '$sid', created_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()");
}
