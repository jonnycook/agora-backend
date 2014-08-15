<?php

require_once(__DIR__.'/../../includes/header.php');
require_once(__DIR__.'/../../includes/ws.php');

// unused
function createShare($user, $withUser, $object, $params=null) {
	global $mysqli;
	$userId = $user['id'];
	if ($object != '/') {
		list($table, $id) = explode('.', $object);
		if ($table == 'decisions') {
			$update = array('shared' => 1);
			if ($params['title']) {
				$update['share_title'] = $params['title'];
			}

			$sql = array();
			foreach ($update as $key => $value) {
				$sql[] = "$key = '$value'";
			}
			$sql = implode(',', $sql);
			mysqli_query($mysqli, "UPDATE m_decisions SET $sql WHERE id = $id");


			sendUpdate($userId, array(
				'decisions' => array("G$id" => $update)
			));
		}
	}

	// *collaboration*
	// $user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_users WHERE id = $userId"));
	$userName = $user['name'] ? $user['name'] : $user['email'];
	$withUserName = $withUser['name'] ? $withUser['name'] : $withUser['email'];

	if ($params['title']) {
		$title = '"' . mysqli_real_escape_string($mysqli, $params['title']) . '"';
	}
	else {
		$title = 'NULL';
	}

	mysqli_query($mysqli, "INSERT INTO shared SET user_id = $userId, object = '$object', title = $title, with_user_id = $withUser[id], created_at = UTC_TIMESTAMP()") or die(mysqli_error($mysqli));
	$id = mysqli_insert_id($mysqli);

	$record = array(
		'id' => $id,
		'object' => $object,
		'user_id' => $userId,
		'with_user_id' => $withUser['id'],
		'seen' => 0,
		'user_name' => $userName,
		'with_user_name' => $withUserName,
	);

	if ($params['title']) {
		$record['title'] = $params['title'];
	}

	sendMessage($userId, 'shared', array(
		'action' => 'create',
		'userId' => $userId,
		'record' => $record,
	));

	sendMessage($withUser['id'], 'shared', array(
		'action' => 'create',
		'userId' => $withUser['id'],
		'record' => $record,
	));

	$row = mysqli_fetch_row(mysqli_query($mysqli, "SELECT COUNT(*) FROM shared WHERE user_id = $userId && object = '$object'"));
	if ($row[0] == 1) {
		$collaborators["G$userId.$object.$userId"] = array(
			'user_id' => $userId,
			'object' => $object,
			'object_user_id' => $userId
		);
	}
	$collaborators["G$userId.$object.$withUser[id]"] = array(
		'user_id' => $withUser['id'],
		'object' => $object,
		'object_user_id' => $userId
	);

	sendMessage($userId, 'collaborators', array(
		'userId' => $userId,
		'object' => $object,
		'changes' => json_encode(array('collaborators' => $collaborators)),
	));
}