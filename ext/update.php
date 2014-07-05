<?php

header('Content-Type: text/plain');
echo json_encode(array(
	'status' => 'down',
	'message' => 'The version of Agora you are using is outdated and no longer supported! Please upgrade. Thank you!'
));

exit;

require_once('includes/header.php');
require_once('includes/update.php');

if (ENV == 'PROD' && !$_POST['debug']) {
	dbErrors();
}
else {
	ini_set('html_errors', 0);
}


// header('Content-Type: application/json');
echo json_encode(update($_POST));
