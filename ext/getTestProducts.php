<?php

require_once('includes/header.php');

if ($_GET['site']) {
	$site = mysqli_real_escape_string($mysqli, $_GET['site']);
	$result = mysqli_query($mysqli, "SELECT site,sid,data FROM test_products WHERE site = '$site'");
}
else {
	$result = mysqli_query($mysqli, "SELECT site,sid,data FROM test_products");
}

$products = array();
while ($row = mysqli_fetch_assoc($result)) {
	$products[$row['site']][$row['sid']] = $row['data'];
}

echo json_encode($products);