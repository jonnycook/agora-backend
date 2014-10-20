<?php

require_once('../includes/header.php');
require_once('../includes/user.php');

$name = mysqli_real_escape_string($mysqli, $_POST['name']);
$email = mysqli_real_escape_string($mysqli, $_POST['email']);

mysqli_query($mysqli, "INSERT INTO m_users SET email = '$email', alerts_email = '$email', name = '$name', created_at = UTC_TIMESTAMP(), `from` = 1");
$id = mysqli_insert_id($mysqli);
mysqli_query($mysqli, "INSERT INTO m_belts SET user_id = $id, creator_id = $id");

setUserId($id);

echo 'ok';