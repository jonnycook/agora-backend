<?php
require_once('includes/header.php');

require_once('../includes/parse.php');

header('Access-Control-Allow-Origin: http://webapp.agora.dev');
header('Access-Control-Allow-Origin: http://agora.sh');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

echo json_encode(parse($_GET['descriptor']));