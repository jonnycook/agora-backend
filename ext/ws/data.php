<?php

require_once('header.php');

// $clientId = $_GET['clientId'];
$userId = mysqli_real_escape_string($mysqli, $_GET['userId']);

// if (!preg_match('/^[A-z0-9]*$/', $clientId)) {
// 	echo 'invalid client id';
// 	exit;
// }

// $userId = getUserId($clientId);

$db = makeDb($userId, null);
$db->queryByUserId = false;

function getCollaborators($userId, $object) {
	// *collaboration*
	global $mysqli;
	$result = mysqli_query($mysqli, "SELECT * FROM shared WHERE user_id = $userId && object = '$object'");
	$collaborators = array();
	while ($row = mysqli_fetch_assoc($result)) {
		$collaborators["G$row[user_id].$row[object].$row[with_user_id]"] = array(
			'object_user_id' => $row['user_id'],
			'object' => $row['object'],
			'user_id' => $row['with_user_id'],
			'role' => $row['role']
		);

		$collaborators["G$row[user_id].$row[object].$row[user_id]"] = array(
			'object_user_id' => $row['user_id'],
			'object' => $row['object'],
			'user_id' => $row['user_id'],
			// 'role' => 'owner',
		);
	}
	return $collaborators;
}

function addActivity(&$data, $row) {
		$id = md5("$row[user_id]$row[timestamp]$row[generator_id]$row[object_type]$row[object_id]$row[type]$row[args]");
		$data["G$id"] = array(
			'user_id' => "G$row[user_id]",
			'timestamp' => $row['timestamp'],
			'generator_id' => "G$row[generator_id]",
			'object_type' => $row['object_type'],
			'object_id' => $row['object_id'],
			'type' => $row['type'],
			'args' => $row['args'],
		);
}

function getAllActivity() {
	global $mysqli, $userId;
	$result = mysqli_query($mysqli, "SELECT * FROM activity WHERE user_id = $userId");
	$data = array();
	while ($row = mysqli_fetch_assoc($result)) {
		addActivity($data, $row);
	}
	return $data;
}

$object = $_GET['object'];
if ($object == '*') {
	$data = $db->data();
	$result = mysqli_query($mysqli, "SELECT * FROM shared WHERE user_id = $userId || with_user_id = $userId");
	while ($row = mysqli_fetch_assoc($result)) {
		$userResult = mysqli_query($mysqli, "SELECT * FROM m_users WHERE id IN ($row[user_id], $row[with_user_id])");
		while ($userRow = mysqli_fetch_assoc($userResult)) {
			if ($userRow['id'] == $row['user_id']) {
				$userName = $userRow['name'] ? $userRow['name'] : $userRow['email'];
			}
			else if ($userRow['id'] == $row['with_user_id']) {
				$withUserName = $userRow['name'] ? $userRow['name'] : $userRow['email'];
			}
		}

		// *collaboration*
		$data['shared_objects']["G$row[id]"] = array(
			'user_id' => "G$row[user_id]",
			'with_user_id' => "G$row[with_user_id]",
			'title' => $row['title'],
			'object' => $row['object'],
			'role' => $row['role'],
			'seen' => $row['seen'],
			'user_name' => $userName,
			'with_user_name' => $withUserName,
		);

		if ($_GET['collaborators']) {
			if ($row['user_id'] == $userId) {
				$data['collaborators']["G$row[user_id].$row[object].$row[with_user_id]"] = array(
					'object_user_id' => $row['user_id'],
					'object' => $row['object'],
					'user_id' => $row['with_user_id'],
					'role' => $row['role'],
				);

				$data['collaborators']["G$row[user_id].$row[object].$row[user_id]"] = array(
					'object_user_id' => $row['user_id'],
					'object' => $row['object'],
					'user_id' => $row['user_id'],
					// 'role' => 'owner',
				);
			}
		}
	}

	if ($_GET['collaborators']) {
		$result = mysqli_query($mysqli, "SELECT * FROM invitations WHERE user_id = $userId && accepted_at IS NULL");
		while ($row = mysqli_fetch_assoc($result)) {
			$action = json_decode($row['action']);
			if ($action->type == 'collaborate') {
				$data['collaborators']["Gp$row[id]"] = array(
					'object_user_id' => $userId,
					'object' => $action->object,
					'role' => $action->role,
					'pending' => true,
					'email' => $row['email'],
					'invitation' => $row['id'],
				);
			}
		}

		$result = mysqli_query($mysqli, "SELECT * FROM notifications WHERE user_id = $userId");
		while ($row = mysqli_fetch_assoc($result)) {
			$data['notifications']["G$row[id]"] = array(
				'type' => $row['type'],
				'object_id' => "G$row[object_id]",
				'seen' => $row['seen'],
				'created_at' => $row['created_at']
			);
		}

		$result = mysqli_query($mysqli, "SELECT * FROM permissions WHERE owner_id = $userId");
		while ($row = mysqli_fetch_assoc($result)) {
			$data['permissions'][dbIdToModelId($row['id'])] = array(
				'user_id' => $row['user_id'],
				'object' => $row['object'],
				'level' => $row['level'],
				'created_at' => $row['created_at'],
			);
		}

		$data['activity'] = getAllActivity();		
	}	
}
else if ($object == '/') {
	$data = $db->prepareData($db->storage->getData(array(
		'elements' => 'Root'
	)));
	if ($_GET['collaborators']) {
		$data['collaborators'] = getCollaborators($userId, $object);
	}
	$data['activity'] = getAllActivity();
}
else if ($object == '@') {
	$data = $db->prepareData($db->storage->getData(array(
		'records' => array('User' => array($userId))
	)));
}
else {
	list($table, $id) = explode('.', $object);
	if ($table == 'decisions') $model = 'Decision';
	else if ($table == 'belts') $model = 'Belt';
	else if ($table == 'lists') $model = 'List';
	else if ($table == 'bundles') $model = 'Bundle';
	else if ($table == 'object_references') $model = 'ObjectReference';
	else throw new Exception("invalid object $object");

	$data = $db->storage->getData(array(
		'records' => array($model => array($id)),
		'products' => 'referenced'
	));

	if ($_GET['claim']) {
		foreach ($data as $table => $records) {
			mysqli_query($mysqli, "UPDATE m_$table SET user_id = $userId WHERE id IN (" . implode(',', array_keys($records)) . ')');
		}
	}

	$data = $db->prepareData($data);

	$clientUserId = getUserId($_GET['clientId']);

	if ($clientUserId != $userId) {
		$sql = "SELECT * FROM permissions WHERE owner_id = $userId && object = '$object' && (user_id IS NULL || user_id = $clientUserId)";
		$result = mysqli_query($mysqli, $sql);
		while ($permissionRow = mysqli_fetch_assoc($result)) {
			if ($permissionRow['user_id']) {
				$permission = $permissionRow['level'];
				break;
			}
			else {
				$permission = $permissionRow['level'];
			}
		}

		if (isset($permission)) {
			$data['permissions']['G' . $object] = array(
				'object' => $object,
				'level' => $permission
			);
		}
	}

	if ($_GET['collaborators']) {
		$data['collaborators'] = getCollaborators($userId, $object);
	}
	$result = mysqli_query($mysqli, "SELECT * FROM activity WHERE user_id = $userId");
	while ($row = mysqli_fetch_assoc($result)) {
		$table = modelNameToTableName($row['object_type']);
		if ($data[$table][$row['object_id']]) {
			addActivity($data['activity'], $row);
		}
	}
}

echo json_encode($data);
