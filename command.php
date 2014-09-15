<?php
xdebug_disable();
require_once('includes/header.php');

$command = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM client_commands WHERE id = $_GET[id]"));


$result = json_decode($command['result']);

header('Content-type: text/plain');

var_export($result);