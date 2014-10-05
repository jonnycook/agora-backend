<?php

require_once('../includes/header.php');

for ($i = 0; $i < 100; ++ $i) {
	$email = $i;
	$name = $i;
	$password = passHash($i);
	mysqli_query($mysqli, "INSERT INTO m_users SET email = '$email', name = '$name', password = '$password', created_at = UTC_TIMESTAMP()") or die(mysqli_error($mysqli));
	$id = mysqli_insert_id($mysqli);
	mysqli_query($mysqli, "INSERT INTO m_belts SET user_id = $id, creator_id = $id");
}
