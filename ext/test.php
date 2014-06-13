<?php

require_once('includes/header.php');
require_once('includes/update.php');

require_once('reset.php');

header('Content-Type: text/plain');

$queryPaths = glob('queries/*');

$userId = 1;
$lastTime = '';

foreach ($queryPaths as $queryPath) {
	echo "$queryPath\n";
	var_dump(json_decode(file_get_contents($queryPath), true));
	$response = update(array(
		'userId' => $userId,
		'lastTime' => $lastTime,
		'extVersion' => 'chrome-0.0.56',
		'apiVersion' => '0.0.1',
		'instanceId' => '1378546528539-037876192736439407',
		'changes' => file_get_contents($queryPath),
	));

	$lastTime = $response['time'];
	var_dump($response);
	sleep(1);
}

