<?php

require_once('header.php');


$type = $_GET['type'];
$id = $_GET['id'];

$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM m_$type WHERE id = $id"));

$userId = userId();
if (!$userId) {
	$userId = $row['user_id'];
}

$db = makeDb($userId, null);
$db->queryByUserId = false;

if ($type == 'decisions') $model = 'Decision';

$data = $db->storage->getData(array(
	'records' => array($model => array($id)),
	'products' => true
));
$data = $db->prepareData($data);

echo json_encode($data);
