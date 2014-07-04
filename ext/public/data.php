<?php

require_once('header.php');

$map = array(
	'decisions' => 'Decision'
);

$type = $_GET['type'];

if (!$map[$type]) exit;

$id = mysqli_real_escape_string($mysqli, $_GET['id']);

$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_$type WHERE id = $id"));

if (!$row['access']) {
	echo '"accessDenied"';
	exit;
}

$userId = userId();
if (!$userId) {
	$userId = $row['user_id'];
}

$db = makeDb($userId, null);
$db->queryByUserId = false;

$model = $map[$type];

$data = $db->storage->getData(array(
	'records' => array($model => array($id)),
	'products' => 'referenced'
));
$data = $db->prepareData($data);

echo json_encode($data);