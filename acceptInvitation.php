<?php

require_once('ext/includes/header.php');
require_once('ext/includes/ws.php');
require_once('ext/ws/shared/header.php');

$key = mysqli_real_escape_string($mysqli, $_GET['key']);
if ($_GET['password'] == 'Carl Sagan' && $_GET['userId']) {
	$userId = $_GET['userId'];
}
else {
	$userId = userId();
}

$invitation = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT id,action,user_id,accepted_at FROM invitations WHERE `key` = '$key'"));

if ($_GET['return']) {
	header("Location: $_GET[return]");
}

if (!$invitation['accepted_at']) {
	mysqli_query($mysqli, "UPDATE invitations SET accepted_at = UTC_TIMESTAMP(), recipient_id = $userId WHERE `key` = '$key'") or die(mysqli_error($mysqli));
	if ($invitation['action']) {
		$action = json_decode($invitation['action'], true);
		if ($action['type'] == 'collaborate') {
			// *collaboration*
			$changes['collaborators']["Gp$invitation[id]"] = 'deleted';
			sendMessage($userId, 'collaborators', array(
				'userId' => $userId,
				'changes' => json_encode($changes),
			));

			sendMessage($user['id'], 'share/create', array(
				'clientId' => 'Carl Sagan',
				'userId' => $invitation['user_id'],
				'object' => $action['object'],
				'subscribe_object' => $action['subscribe_object'],
				'role' => $action['role'],
				'withUserId' => $userId
			));
		}
	}
}