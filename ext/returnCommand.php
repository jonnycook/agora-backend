<?php

require_once('includes/header.php');

$result = mysqli_real_escape_string($mysqli, $_POST['result']);
$id = mysqli_real_escape_string($mysqli, $_POST['commandId']);
mysqli_query($mysqli, "UPDATE client_commands SET `result` = '$result', responded_at = UTC_TIMESTAMP(), stage=2 WHERE id = $id") or die(mysqli_error($mysqli));
