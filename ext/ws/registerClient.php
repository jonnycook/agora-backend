<?php

require_once('header.php');
require_once('../../includes/user.php');

if (defined('TESTING')) {
	if (!isset($_GET['tester'])) {
		echo '"not signed in"';
		exit;
	}

	$userId = $_GET['tester'];
}
else {
	$userId = userId();
}

$instanceId = mysqli_real_escape_string($mysqli, $_GET['instanceId']);

// if (!$userId) {
// 	$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT id FROM m_users WHERE instance_id = '$instanceId'"));
// 	if ($user) {
// 		$userId = intval($user['id']);
// 	}
// 	else {
// 		if ($_COOKIE['clickId']) {
// 			$clickId = '"' . mysqli_real_escape_string($mysqli, $_COOKIE['clickId']) . '"';
// 			setcookie('clickId', 0, time() - 1000, '/', '.' . DOMAIN);		
// 		}
// 		else {
// 			$clickId = 'NULL';
// 		}

// 		$ip = remoteAddr();
// 		mysqli_query($mysqli, 'INSERT INTO m_users SET 
// 			created_at = UTC_TIMESTAMP(), 
// 			ip = "' . $ip . '", user_agent = "' . mysqli_real_escape_string($mysqli, $_SERVER['HTTP_USER_AGENT']) . '",
// 			instance_id = "' . $instanceId . '",
// 			click_id = ' . $clickId) or die(mysqli_error($mysqli));
// 		$userId = mysqli_insert_id($mysqli);
// 		mysqli_query($mysqli, "INSERT INTO m_belts SET user_id = $userId, creator_id = $userId") or die(mysqli_error($mysqli));
// 	}

// 	$signedIn = true;
// }

if ($userId != null) {
	setUserId($userId);
	$extVersion = mysqli_real_escape_string($mysqli, $_GET['extVersion']);

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
		$server = $updaterServers[mt_rand(0, count($updaterServers) - 1)];
	}

	$clientId = md5(rand());
	mysqli_query($mysqli, "UPDATE m_users SET ext_version = '$extVersion' WHERE id = $userId"); 

	if ($_GET['subscribes']) {
		$subscribes = 1;
	}
	else {
		$subscribes = 0;
	}
	mysqli_query($mysqli, "INSERT INTO clients SET client_id = '$clientId', instance_id = '$instanceId', user_id = $userId, created_at = UTC_TIMESTAMP(), last_seen_at = UTC_TIMESTAMP(), `version` = '$extVersion', updater_server = '$server', subscribes = $subscribes");
	$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT track, click_id, converted FROM m_users WHERE id = $userId"));

	$response = array('status' => 'success', 'clientId' => $clientId, 'userId' => $userId, 'updaterServer' => $server, 'updateToken' => newUpdateToken($userId, $clientId));

	if ($user['click_id'] && !$user['converted']) {
		$response['convert'] = true;
		// $response['clickId'] = $user['click_id'];
	}

	if ($signedIn) {
		$response['signedIn'] = true;
	}

	if ($user['track']) {
		$response['track'] = $user['track'];
	}

	echo json_encode($response);
}
else {
	echo '"not signed in"';
}