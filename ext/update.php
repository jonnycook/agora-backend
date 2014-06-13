<?php

require_once('includes/header.php');
require_once('includes/update.php');
header('Content-Type: text/plain');

if (ENV == 'PROD' && !$_POST['debug']) {
	dbErrors();
}
else {
	ini_set('html_errors', 0);
}

// header('Content-Type: application/json');
echo json_encode(update($_POST));
