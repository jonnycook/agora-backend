<?php

require_once('../includes/header.php');
require_once('../includes/user.php');

header('Access-Control-Allow-Origin: http://webapp.agora.dev');
header('Access-Control-Allow-Credentials: true');


$name = mysqli_real_escape_string($mysqli, $_POST['name']);
$email = mysqli_real_escape_string($mysqli, $_POST['email']);

mysqli_query($mysqli, "INSERT INTO m_users SET email = '$email', alerts_email = '$email', name = '$name', created_at = UTC_TIMESTAMP()");
$id = mysqli_insert_id($mysqli);
mysqli_query($mysqli, "INSERT INTO m_belts SET user_id = $id, creator_id = $id");

setUserId($id);

echo 'ok';