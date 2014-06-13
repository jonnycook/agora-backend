<?php

require_once('header.php');

$userId = userId();
$object = mysqli_real_escape_string($mysqli, $_POST['object']);
if ($_POST['title']) {
	$title = '"' . mysqli_real_escape_string($mysqli, $_POST['title']) . '"';
}
else {
	$title = 'NULL';
}

$with = mysqli_real_escape_string($mysqli, $_POST['with']);
$withUser = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_users WHERE email = '$with'"));
if ($withUser) {
	if ($object != '/') {
		list($table, $id) = explode('.', $object);
		if ($table == 'decisions') {
			mysqli_query($mysqli, "UPDATE m_decisions SET shared = 1, share_title = $title WHERE id = $id");
			sendUpdate($userId, array(
				'decisions' => array("G$id" => array('share_title' => $_POST['title'], 'shared' => 1))
			));
		}
	}
	$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_users WHERE id = $userId"));
	$userName = $user['name'] ? $user['name'] : $user['email'];
	$withUserName = $withUser['name'] ? $withUser['name'] : $withUser['email'];

	mysqli_query($mysqli, "INSERT INTO shared SET user_id = $userId, object = '$object', title = $title, with_user_id = $withUser[id], created_at = UTC_TIMESTAMP()") or die(mysqli_error($mysqli));
	$id = mysqli_insert_id($mysqli);

	$record = array(
		'id' => $id,
		'object' => $_POST['object'],
		'user_id' => $userId,
		'with_user_id' => $withUser['id'],
		'title' => $_POST['title'],
		'user_name' => $userName,
		'with_user_name' => $withUserName,
	);

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
else {
	echo 'no user';
}
