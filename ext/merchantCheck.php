<?php

echo 0;
exit;

require_once('includes/header.php');


$userId = userId();
if ($userId) {
	mysqli_query($mysqli, "UPDATE m_users SET last_merchant_check = UTC_TIMESTAMP() WHERE id = $userId");
}

$host = $_GET['host'];
if (!strncmp($host, 'www.', 4)) {
	$host = substr($host, 4);
}
if (mysqli_fetch_row(mysqli_query($mysqli, 'SELECT 1 FROM merchants WHERE domain = "' . mysqli_real_escape_string($mysqli, $host) . '"'))) {
	echo 1;
}
else {
	echo 0;
}
