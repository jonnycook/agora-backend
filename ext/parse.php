<?php
require_once('includes/header.php');

require_once('../includes/parse.php');

echo json_encode(parse($_GET['descriptor']));