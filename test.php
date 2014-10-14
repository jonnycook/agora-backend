

<?php

function emailBody($styles) {
	extract($styles);
	$imageRoot = "http://agora.sh/images/email/welcome/";

	?>
		<div style="font-size:22px; padding:32px; ">Welcome to Agora! Super Powers For the Online Shopper</div>
		<img style="margin-left:14px" src="<?php echo $imageRoot ?>header.png">
		<div style="padding:32px">
			<p>Thank you for installing Agora! If you didn't get to watch our 2-minute overview tutorial video you can click below to watch it:</p>
			<a href="http://youtu.be/uS553zIBHHo"><img src="<?php echo $imageRoot ?>overviewVideo.png"></a>
			<p>Over the next few months, we'll be emailing you some tutorials that will teach you how to get the most out of Agora.</p>
			<p>You can access our video tutorials by visiting <a href="http://agora.sh/tutorials.html" style="<?php echo $linkStyles ?>">http://agora.sh/tutorials.html</a> or by clicking the question mark icon on the Agora Belt.</p>
			<img src="<?php echo $imageRoot ?>image.png">
			<p>Finally, check out our list of growing <a styles="<?php echo $linkStyles ?>" href="http://agora.sh/supportedSites.html">supported sites</a>.</p>
			<p>If you have any other questions please send us an email at <a href="mailto:contact@agora.sh" style="<?php echo $linkStyles ?>">contact@agora.sh</a>. We want to hear from you!</p>
			<p>Cheers,<br>
			The Agora Team
			</p>
		</div>
	<?php
}

require_once('emailTemplates/template.php');
require_once('includes/swift/swift_required.php');
// mail($_POST['with'], "$userName sent you an invitation", $body);

$body = emailTemplate_template(array(
	'title' => 'Welcome',
	'body' => emailBody,
	// 'unsubscribe'
));

echo $body;
// exit;

// $msg = Swift_Message::newInstance(); 
// $msg->setEncoder(Swift_Encoding::get8BitEncoding());
// $msg->setSubject("Test");
// $msg->setFrom(array('contact@agora.sh' => 'Test'));
// $msg->setTo('qubsoft@gmail.com');
// $msg->setContentType("text/html");
// $msg->setBody($body);

// if ($user['email']) {
// 	$msg->setReplyTo($user['email']);
// }

// $transport = Swift_SmtpTransport::newInstance('smtp.mandrillapp.com', 587)
//   ->setUsername('dev@agora.sh')
//   ->setPassword('jXbapyP6xD24QSTInW_UgQ');

// $mailer = Swift_Mailer::newInstance($transport);
// echo $mailer->send($msg);



$ch = curl_init('http://ext.agora.sh/email.php');
curl_setopt_array($ch, array(
	CURLOPT_POSTFIELDS => array(
		'fromEmail' => 'contact@agora.sh',
		'fromName' => 'Test',
		'to' => 'themichaelcook@gmail.com, qubsoft@gmail.com, finkin@gmail.com',
		'subject' => 'Test',
		'body' => $body
	)
));
curl_exec($ch);