<?php

require_once(__DIR__.'/../includes/header.php');
require_once(__DIR__.'/../init.php');

function newUpdateToken($userId, $clientId) {
	global $mysqli;
	mysqli_query($mysqli, "INSERT INTO update_tokens SET user_id = $userId, client_id = '$clientId'") or die(mysqli_error($mysqli));
	return mysqli_insert_id($mysqli);
}


function getUserId($clientId) {
	global $mysqli;
	$row = mysqli_fetch_row(mysqli_query($mysqli, "SELECT user_id FROM clients WHERE client_id = '$clientId'"));

	if (!$row) {
		die('invalid client id');
	}
	return $row[0];
}
