<?php

require_once('includes/swift/swift_required.php');

$email = $_POST['from']['email'];
$name = $_POST['from']['name'];
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

$transport = Swift_SmtpTransport::newInstance('smtp.sendgrid.com', 25)
  ->setUsername('buylater')
  ->setPassword('s50meep10');

$mailer = Swift_Mailer::newInstance($transport);
echo $mailer->send($msg);
