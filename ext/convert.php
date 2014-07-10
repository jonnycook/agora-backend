<?php

require_once('includes/header.php');
$userId = userId();

$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT click_id, converted FROM m_users WHERE id = $userId"));
var_dump($user);
if (!$user['converted']) {
	// $ch = curl_init("http://api.socialingot.com/postback/agora/?sid=$user[click_id]");
	// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	// curl_exec($ch);
	mysqli_query($mysqli, "UPDATE m_users SET converted = 1 WHERE id = $userId");
}
