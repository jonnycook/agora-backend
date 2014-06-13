<?php

require_once('includes/header.php');

$data = json_decode($_POST['data'], true);
$obj = mysqli_real_escape_string($mysqli, $_POST['data']);
mysqli_query($mysqli, "INSERT INTO scrapers SET
	site='${data['site']}', 
	name='${data['name']}', 
	version='${data['version']}', 
	obj=\"$obj\"") or die(mysqli_error($mysqli));