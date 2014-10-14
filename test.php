<?php

require_once('emailTemplates/template.php');
require_once('includes/swift/swift_required.php');
// mail($_POST['with'], "$userName sent you an invitation", $body);

$body = emailTemplate_template(array());

$msg = Swift_Message::newInstance(); 
$msg->setEncoder(Swift_Encoding::get8BitEncoding());
$msg->setSubject("Test");
// $msg->setFrom(array($user['email'] => $user['name']));
$msg->setTo('qubsoft@gmail.com');
$msg->setContentType("text/html");
$msg->setBody($body);

if ($user['email']) {
	$msg->setReplyTo($user['email']);
}

$transport = Swift_SmtpTransport::newInstance('smtp.mandrillapp.com', 587)
  ->setUsername('dev@agora.sh')
  ->setPassword('DkKsg5zShP-aDxunIbgJaA');

$mailer = Swift_Mailer::newInstance($transport);
echo $mailer->send($msg);