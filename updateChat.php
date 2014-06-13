<?php

require_once('ext/includes/header.php');
$userId = userId();

if ($_REQUEST['lastRead']) {
	$lastRead = mysqli_real_escape_string($mysqli, $_REQUEST['lastRead']);
	mysqli_query($mysqli, "UPDATE m_users SET last_read_message = $lastRead WHERE id = $userId");
}

if ($_REQUEST['messages']) {
	foreach ($_REQUEST['messages'] as $message) {
		$message = mysqli_real_escape_string($mysqli, $message);
		mysqli_query($mysqli, "INSERT INTO chat_messages SET content = '$message', user_id = $userId");		
	}
}

$last = $_REQUEST['last'] ? $_REQUEST['last'] : 0;

$response = array();

if ($last) {
	$escapedLast = mysqli_real_escape_string($mysqli, $last);
	$result = mysqli_query($mysqli, "SELECT id, sender, content, `timestamp` FROM chat_messages WHERE user_id = $userId && id > $escapedLast");
}
else {
	$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT last_read_message FROM m_users WHERE id = $userId"));
	$response['lastRead'] = $user['last_read_message'];
	$result = mysqli_query($mysqli, "SELECT id, sender, content, `timestamp` FROM chat_messages WHERE user_id = $userId");
}

$messages = array();
while ($row = mysqli_fetch_assoc($result)) {
	if (!$last || $row['id'] > $last) $last = $row['id'];
	$messages[] = $row;
}

// $messages = mysqli_real_escape_string($mysqli, json_encode($messages));

//mysqli_query($mysqli, "INSERT INTO logs SET message = '$messages; $userId; $lastTime'");

echo json_encode($response + array(
	'last' => $last,
	'messages' => $messages,
	'updateInterval' => 20000,
	'online' => true,
	'writingReply' => array()
));