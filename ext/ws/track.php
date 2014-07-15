<?php

require_once('header.php');

$userId = getUserId($_GET['clientId']);

$args = $_POST['args'];
$type = $args[0];
$args = array_slice($args, 1);

function resolveArgs($args, $num) {
	global $mysqli;
	$result = array();
	for ($i = 0; $i < $num; ++ $i) {
		if ($args[$i] === null || $args[$i] === '') {
			$result[] = 'NULL';
		}
		else {
			$result[] = '"' . mysqli_real_escape_string($mysqli, $args[$i]) . '"';
		}
	}
	return $result;
}

if ($type == 'visit') {
	$siteId = Site::siteForName($args[0])->id;
	mysqli_query($mysqli, "INSERT INTO visited SET user_id = $userId, site_id = $siteId");
}
else if ($type == 'event') {
	list($object, $action, $param) = resolveArgs($args, 3);
	mysqli_query($mysqli, "INSERT INTO tracking_events SET 
		user_id = $userId,
		object = $object,
		action = $action,
		param = $param");
}
else if ($type == 'page') {
	list($page) = resolveArgs($args, 1);
	mysqli_query($mysqli, "INSERT INTO tracking_pages SET 
		user_id = $userId,
		page = $page");
}
else if ($type == 'time') {
	list($category, $variable, $time, $label) = resolveArgs($args, 4);
	mysqli_query($mysqli, "INSERT INTO tracking_time SET 
		user_id = $userId,
		category = $category,
		variable = $variable,
		time = $time,
		label = $label");
}
