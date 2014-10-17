<?php

require_once('includes/header.php');

function insert($values, $table) {
	global $mysqli;
	foreach ($values as $field => $value) {
		$setters[] = "`$field` = '" . mysqli_real_escape_string($mysqli, $value) . '\'';
	}

	$query = "INSERT INTO `$table` SET " . implode(',', $setters);
	mysqli_query($mysqli, $query) or die("$query: " . mysqli_error($mysqli));
	return mysqli_insert_id($mysqli);
}

$id = insert(array(
	'user_id' => userId(),
	'subject' => $_POST['subject'],
	'message' => $_POST['message'],
	'page' => $_POST['page'],
	'extVersion' => $_POST['extVersion'],
	'apiVersion' => $_POST['apiVersion'],
	'instanceId' => $_POST['instanceId'],
	'ip' => remoteAddr()
), 'contact_log');

if ($userId = userId()) {
	$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_users WHERE id = $userId"));
}

if ($user['email']) {
	$email = $user['email'];
}
else {
	$email = 'no-email@agora.sh';
}

require_once('../includes/swift/swift_required.php');

$msg = Swift_Message::newInstance(); 
$msg->setEncoder(Swift_Encoding::get8BitEncoding());
$msg->setSubject($_POST['subject']);

$msg->setFrom(array($email => $user['name']));
$msg->setTo('contact@agora.sh');
$msg->setBody($_POST['message']);

$msg->setReplyTo($email);

$transport = Swift_SmtpTransport::newInstance('smtp.mandrillapp.com', 25)
  ->setUsername('dev@agora.sh')
  ->setPassword('DkKsg5zShP-aDxunIbgJaA');

$mailer = Swift_Mailer::newInstance($transport);

echo $mailer->send($msg);
