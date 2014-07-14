<?php

require_once('header.php');

$userId = userId();

if ($userId != null) {
	$extVersion = mysqli_real_escape_string($mysqli, $_GET['extVersion']);
	$instanceId = mysqli_real_escape_string($mysqli, $_GET['instanceId']);

	if (ENV == 'LOCAL_DEV') {
		$server = 'localhost:8080';
	}
	else if (ENV == 'LINODE_DEV') {
		$server = '66.228.54.96:8081';
	}
	else {
		$result = mysqli_query($mysqli, 'SELECT ip FROM servers WHERE type = "router"');
		$updaterServers = array();
		while ($row = mysqli_fetch_row($result)) {
			$updaterServers[] = $row[0];
		}
		$server = $updaterServers[rand(0, count($updaterServers) - 1)];
	}

	$clientId = md5(rand());
	mysqli_query($mysqli, "UPDATE m_users SET ext_version = '$extVersion' WHERE id = $userId"); 
	mysqli_query($mysqli, "INSERT INTO clients SET client_id = '$clientId', instance_id = '$instanceId', user_id = $userId, created_at = UTC_TIMESTAMP(), last_seen_at = UTC_TIMESTAMP(), `version` = '$extVersion', updater_server = '$server'");
	$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT track, click_id, converted FROM m_users WHERE id = $userId"));

	$response = array('status' => 'success', 'clientId' => $clientId, 'userId' => $userId, 'updaterServer' => $server, 'updateToken' => newUpdateToken($userId, $clientId));

	if ($user['click_id'] && !$user['converted']) {
		$response['convert'] = true;
		// $response['clickId'] = $user['click_id'];
	}

	if ($user['track']) {
		$response['track'] = $user['track'];
	}

	echo json_encode($response);
}
else {
	echo '"not signed in"';
}