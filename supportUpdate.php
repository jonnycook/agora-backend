<?php

header('Access-Control-Allow-Origin: *');

require_once('includes/header.php');

ini_set('html_errors', 0);

$supportStaffId = mysqli_real_escape_string($mysqli, $_GET['id']);

if ($_POST['messages']) {
	foreach ($_POST['messages'] as $message) {
		$userId = mysqli_real_escape_string($mysqli, $message['userId']);
		$message = mysqli_real_escape_string($mysqli, $message['message']);

		mysqli_query($mysqli, "INSERT INTO chat_messages SET user_id = $userId, sender = $supportStaffId, content = '$message'");
	}
}

if ($_POST['time']) {
	$lastUpdate = mysqli_real_escape_string($mysqli, $_POST['time']);
	$result = mysqli_query($mysqli, "SELECT * FROM chat_messages WHERE `timestamp` > '$lastUpdate'");
}
else {
	$result = mysqli_query($mysqli, "SELECT * FROM chat_messages");
}


$userIds = array();

$messages = array();
$time = $_POST['time'];
while ($row = mysqli_fetch_assoc($result)) {
	if (!$time || $row['timestamp'] > $time) $time = $row['timestamp'];
	$messages[] = $row;
	$userIds[$row['user_id']] = true;
}

$userIds = array_keys($userIds);

$users = array();

if ($userIds) {
	$result = mysqli_query($mysqli, 'SELECT name, email, id FROM m_users WHERE id IN (' . implode(',', $userIds) . ')');
	while ($row = mysqli_fetch_assoc($result)) {
		$users[] = $row;
	}
}

$result = mysqli_query($mysqli, 'SELECT * FROM support_staff');
$supportStaff = array();
while ($row = mysqli_fetch_assoc($result)) {
	$supportStaff[] = $row;
}

echo json_encode(array(
	'time' => $time,
	'messages' => $messages,
	'supportStaff' => $supportStaff,
	'users' => $users,
));
