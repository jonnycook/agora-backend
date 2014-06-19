<?php

$user = array('email' => 'qubsoft@gmail.com', 'name' => 'Jonny');

require_once('emailTemplates/collaborateInvitation.php');
$email = emailTemplate_collaborateInvitation();
require_once('includes/swift/swift_required.php');
// var_dump(mail('qubsoft@gmail.com', 'Collaborate Invitation', $email));

$msg = Swift_Message::newInstance(); 
$msg->setEncoder(Swift_Encoding::get8BitEncoding());
$msg->setSubject('Collaborate Invitation');
$msg->setFrom(array($user['email'] => $user['name']));
$msg->setTo('qubsoft@gmail.com');
$msg->setContentType("text/html");
$msg->setBody($email);

$msg->setReplyTo($user['email']);

$transport = Swift_SmtpTransport::newInstance('smtp.sendgrid.com', 25)
  ->setUsername('buylater')
  ->setPassword('s50meep10');

$mailer = Swift_Mailer::newInstance($transport);

echo $mailer->send($msg);
