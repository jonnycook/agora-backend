<?php
require_once('includes/header.php');
require_once('../includes/parse.php');
header('Content-Type: application/json');
echo json_encode(parse($_GET['descriptor']));