<?php

require_once('header.php');

$userId = getUserId($_GET['clientId']);

$args = $_POST['args'];

if ($args[0] == 'visit') {
	$siteId = Site::siteForName($args[1])->id;
	mysqli_query($mysqli, "INSERT INTO visited SET user_id = $userId, site_id = $siteId");
}
