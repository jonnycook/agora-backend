<?php

require_once('includes/header.php');

$id = mysqli_real_escape_string($mysqli, $_GET['id']);
$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT result FROM client_commands WHERE id = $id"));

echo json_decode($row['result']);
