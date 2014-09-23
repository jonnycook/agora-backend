<?php

require_once('includes/swift/swift_required.php');

$email = $_POST['fromEmail'];
$name = $_POST['fromName'];
$msg = Swift_Message::newInstance(); 
$msg->setEncoder(Swift_Encoding::get8BitEncoding());
$msg->setSubject($_POST['subject']);
$msg->setFrom(array($email => $name));
$msg->setTo($_POST['to']);
$msg->setContentType("text/html");
$msg->setBody($_POST['body']);

if ($_POST['replyTo']) {
	$msg->setReplyTo($_POST['replyTo']);
}

$transport = Swift_SmtpTransport::newInstance('smtp.mandrillapp.com', 25)
  ->setUsername('dev@agora.sh')
  ->setPassword('DkKsg5zShP-aDxunIbgJaA');

$mailer = Swift_Mailer::newInstance($transport);
echo $mailer->send($msg);
