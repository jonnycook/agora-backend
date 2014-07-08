<?php

require_once('header.php');
define('GET_RIAK_FIELDS', true);

$map = array(
	'decisions' => 'Decision'
);

$type = $_GET['type'];

if (!$map[$type]) exit;

$garbledId = '72881e';
$id = '';
$hashPart = '';
for ($i = 0; $i < strlen($garbledId); ++ $i) {
	if ($i % 2) {
		$hashPart .= $garbledId[$i];
	}
	else {
		$id .= $garbledId[$i];
	}
}

$hash = md5($id . 'salty apple sauce');

if (substr($hash, 0, strlen($hashPart)) != $hashPart) {
	echo '"invalidId"';
	exit;
}

$id = mysqli_real_escape_string($mysqli, $id);

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

echo json_encode(array('data' => $data, 'id' => $id));
