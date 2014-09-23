<?php

require_once('header.php');

$userId = $_GET['userId'];
$object = mysqli_real_escape_string($mysqli, $_POST['object']);

$title = $_POST['title'];
$message = $_POST['message'];
$contributor = $_POST['contributor'];
if ($contributor) {
	$role = 1;
}
else {
	$role = 0;
}

if ($_POST['withUserId']) {
	$withUser = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_users WHERE id = $_POST[withUserId]"));
}
else {
	$with = mysqli_real_escape_string($mysqli, $_POST['with']);
	$withUser = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_users WHERE email = '$with'"));
}
if ($withUser) {
	$count = mysqli_fetch_row(mysqli_query($mysqli, "SELECT COUNT(*) FROM shared WHERE object = '$object' && user_id = $userId && with_user_id = $withUser[id]"));
	if ($count[0]) {
		return;
	}
	if ($object != '/') {
		list($table, $id) = explode('.', $object);
		if ($table == 'decisions') {
			$update = array('shared' => 1);
			if ($title) {
				$update['share_title'] = $title;
			}
			if ($message) {
				$update['share_message'] = $message;
			}

			$sql = array();
			foreach ($update as $key => $value) {
				$sql[] = "$key = '" . mysqli_real_escape_string($mysqli, $value) . "'";
			}
			$sql = implode(',', $sql);
			mysqli_query($mysqli, "UPDATE m_decisions SET $sql WHERE id = $id");


			sendUpdate($userId, array(
				'decisions' => array("G$id" => $update)
			));
		}
		else if ($table == 'belts') {
			$update = array('shared' => 1);
			if ($title) {
				$update['title'] = $title;
			}
			if ($message) {
				// $update['share_message'] = $message;
			}

			$sql = array();
			foreach ($update as $key => $value) {
				$sql[] = "$key = '" . mysqli_real_escape_string($mysqli, $value) . "'";
			}
			$sql = implode(',', $sql);
			mysqli_query($mysqli, "UPDATE m_belts SET $sql WHERE id = $id");


			sendUpdate($userId, array(
				'belts' => array("G$id" => $update)
			));
		}
	}
	$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_users WHERE id = $userId"));
	$userName = $user['name'] ? $user['name'] : $user['email'];
	$withUserName = $withUser['name'] ? $withUser['name'] : $withUser['email'];

	if ($title) {
		$sqlTitle = '"' . mysqli_real_escape_string($mysqli, $title) . '"';
	}
	else {
		$sqlTitle = 'NULL';
	}


	// *collaboration*
	mysqli_query($mysqli, "INSERT INTO shared SET user_id = $userId, object = '$object', title = $sqlTitle, with_user_id = $withUser[id], role = $role, created_at = UTC_TIMESTAMP()") or die(mysqli_error($mysqli));
	$id = mysqli_insert_id($mysqli);

	$record = array(
		'id' => $id,
		'object' => $_POST['object'],
		'user_id' => $userId,
		'with_user_id' => $withUser['id'],
		'seen' => 0,
		'user_name' => $userName,
		'with_user_name' => $withUserName,
		'role' => $role,
	);

	if ($title) {
		$record['title'] = $title;
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
		'object_user_id' => $userId,
		'role' => $role,
	);

	sendMessage($userId, 'collaborators', array(
		'userId' => $userId,
		'object' => $object,
		'changes' => json_encode(array('collaborators' => $collaborators)),
	));
}
else {
	if ($object != '/') {
		list($table, $id) = explode('.', $object);
		if ($table == 'decisions') {
			mysqli_query($mysqli, "UPDATE m_decisions SET shared = 1, share_title = $title WHERE id = $id");
			sendUpdate($userId, array(
				'decisions' => array("G$id" => array('share_title' => $_POST['title'], 'shared' => 1))
			));
		}
		else if ($table == 'belts') {
			mysqli_query($mysqli, "UPDATE m_belts SET shared = 1, title = $title WHERE id = $id");
			sendUpdate($userId, array(
				'belts' => array("G$id" => array('title' => $_POST['title'], 'shared' => 1))
			));
		}
	}

	$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_users WHERE id = $userId"));

	// *collaboration*
	$userName = $user['name'];
	$key = md5($userId . time() . 'I like popsicles.' . rand());
	$action = json_encode(array('type' => 'collaborate', 'object' => $object, 'role' => $role));
	mysqli_query($mysqli, "INSERT INTO invitations SET 
		`key` = '$key',
		user_id = $userId, 
		created_at = UTC_TIMESTAMP(), 
		email = '$with', 
		action = '$action'") or die(mysqli_error($mysqli));

	$invitationId = mysqli_insert_id($mysqli);
	$changes['collaborators']["Gp$invitationId"] = array(
		'object_user_id' => $userId,
		'object' => $object,
		'pending' => true,
		'email' => $_POST['with'],
		'invitation' => $invitationId,
		'role' => $role,
	);
	sendMessage($userId, 'collaborators', array(
		'userId' => $userId,
		'changes' => json_encode($changes),
	));


	require_once('../../../emailTemplates/collaborateInvitation.php');
	$body = emailTemplate_collaborateInvitation(array(
		'name' => $userName,
		'title' => $title,
		'message' => $message,
		'url' => 'http://' . SITE_DOMAIN . '/invitation.php?key=' . $key,
	));
	require_once('../../../includes/swift/swift_required.php');
	// mail($_POST['with'], "$userName sent you an invitation", $body);

	$msg = Swift_Message::newInstance(); 
	$msg->setEncoder(Swift_Encoding::get8BitEncoding());
	$msg->setSubject("$userName sent you an invitation");
	$msg->setFrom(array($user['email'] => $user['name']));
	$msg->setTo($_POST['with']);
	$msg->setContentType("text/html");
	$msg->setBody($body);

	if ($user['email']) {
		$msg->setReplyTo($user['email']);
	}

	$transport = Swift_SmtpTransport::newInstance('smtp.mandrillapp.com', 25)
	  ->setUsername('dev@agora.sh')
	  ->setPassword('DkKsg5zShP-aDxunIbgJaA');

	$mailer = Swift_Mailer::newInstance($transport);
	echo $mailer->send($msg);

}
