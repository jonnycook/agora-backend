<?php

require_once('header.php');
define('GET_RIAK_FIELDS', true);

$userId = userId();


$map = array(
	'decisions' => 'Decision'
);

$type = $_GET['type'];

if (!$map[$type]) exit;

$garbledId = $_GET['id'];
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

$object = "$type.$id";
$sql = "SELECT * FROM permissions WHERE owner_id = $row[user_id] && object = '$object'";

if ($userId) {
	$sql .= " && (user_id IS NULL || user_id = $userId)";
}
else {
	$sql .= " && user_id IS NULL";
}

$result = mysqli_query($mysqli, $sql);
while ($permissionRow = mysqli_fetch_assoc($result)) {
	if ($permissionRow['user_id'] == $userId) {
		$permission = $permissionRow['level'];
		break;
	}
	else {
		$permission = $permissionRow['level'];
	}
}

var_dump($permission);

if ($userId != $row['user_id'] && !$permission) {
	echo '"accessDenied"';
	exit;
}

// if ($userId != $row['user_id'] && !$row['access']) {
// 	echo '"accessDenied"';
// 	exit;
// }


if (!$userId) {
	$userId = $row['user_id'];
}

$db = makeDb($userId, null);
$db->queryByUserId = false;

$model = $map[$type];

$data = $db->storage->getData(array(
	'records' => array($model => array($id)),
	'products' => 'referenced',
));

$data = $db->prepareData($data);

if (isset($permission)) {
	$data['permissions']['G' . $object] = array(
		'object' => $object,
		'level' => $permission
	);
}

echo json_encode(array('data' => $data, 'id' => $id, 'userId' => $row['user_id']));