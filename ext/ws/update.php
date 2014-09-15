<?php

ini_set('html_errors', 0);

function processId($id) {
	global $mysqli;
	if ($id) {
		return mysqli_real_escape_string($mysqli, substr($id, 1));
	}
	else {
		return 'NULL';
	}
}

function processString($string) {
	global $mysqli;
	return '"' . mysqli_real_escape_string($mysqli, $string) . '"';
}

function processInt($int) {
	global $mysqli;
	return mysqli_real_escape_string($mysqli, $int);
}

function notification($userId, $type, $objectId, $record) {
	global $db, $mysqli;

	$types = array(
		'newFeedbackComment' => array(
			'id' => 0,
			'table' => 'feedback_comments'
		),
		'newFeedbackCommentReply' => array(
			'id' => 1,
			'table' => 'feedback_comment_replies'
		),
		'newFeedbackItemReply' => array(
			'id' => 2,
			'table' => 'feedback_item_replies'
		),
	);

	$typeId = $types[$type]['id'];

	mysqli_query($mysqli, "INSERT INTO notifications SET user_id = $userId, type = $typeId, object_id = $objectId") or die(mysqli_error($mysqli));

	$id = mysqli_insert_id($mysqli);

	sendMessage($userId, 'sendUpdate', array(
		'userId' => $userId,
		'changes' => json_encode(array('notifications' => array(
			"G$id" => array(
				'type' => $typeId,
				'object_id' => 'G' . $db->storage->tableHandler($types[$type]['table'])->deriveModelIdFromStorageRecord($types[$type]['table'], $record),
				'seen' => 0,
				'created_at' => gmdate('Y-m-d H:i:s')
			)
		))),
	));
}

function onInsert($table, $id) {
	global $db, $mysqli;
	if ($table == 'feedback_comments') {
		$record = $db->storage->getRecord($table, $id);
		if ($record['creator_id'] != $record['user_id']) {
			notification($record['user_id'], 'newFeedbackComment', $id, $record);
		}
	}
	else if ($table == 'feedback_comment_replies') {
		$record = $db->storage->getRecord($table, $id);
		$commentRecord = $db->storage->getRecord('feedback_comments', $record['feedback_comment_id']);

		$result = mysqli_query($mysqli, "SELECT DISTINCT creator_id FROM m_feedback_comment_replies WHERE feedback_comment_id = $record[feedback_comment_id]");

		while ($row = mysqli_fetch_assoc($result)) {
			if ($commentRecord['creator_id'] != $row['creator_id'] && $record['creator_id'] != $row['creator_id']) {
				notification($row['creator_id'], 'newFeedbackCommentReply', $id, $record);
			}
		}

		if ($commentRecord['creator_id'] != $record['creator_id']) {
			notification($commentRecord['creator_id'], 'newFeedbackCommentReply', $id, $record);
		}
	}
	else if ($table == 'feedback_item_replies') {
		$record = $db->storage->getRecord($table, $id);
		$commentRecord = $db->storage->getRecord('feedback_items', $record['feedback_item_id']);

		$result = mysqli_query($mysqli, "SELECT DISTINCT creator_id FROM m_feedback_item_replies WHERE feedback_item_id = $record[feedback_item_id]");

		while ($row = mysqli_fetch_assoc($result)) {
			if ($commentRecord['creator_id'] != $row['creator_id'] && $record['creator_id'] != $row['creator_id']) {
				notification($row['creator_id'], 'newFeedbackItemReply', $id, $record);
			}
		}

		if ($commentRecord['creator_id'] != $record['creator_id']) {
			notification($commentRecord['creator_id'], 'newFeedbackItemReply', $id, $record);
		}
	}
}

require_once('header.php');
require_once(__DIR__.'/../includes/ws.php');


$clientId = $_GET['clientId'];
$userId = $_GET['userId'];

if ($clientId != 'Carl Sagan') {
	$updateToken = $_POST['updateToken'];

	if (!preg_match('/^[A-z0-9]+$/', $clientId)) {
		echo 'invalid client id';
		exit;
	}
	
	if (!preg_match('/^\d+$/', $updateToken)) {
		echo 'invalid update token';
		exit;
	}

	$clientUserId = getUserId($clientId);
	
	if (ENV != 'TEST') {
		$row = mysqli_fetch_row(mysqli_query($mysqli, "SELECT 1 FROM update_tokens WHERE user_id = $clientUserId && id = $updateToken"));
		if (!$row) {
			echo json_encode(array(
				'status' => 'invalidUpdateToken',
				'updateToken' => newUpdateToken($clientUserId, $clientId)
			));
			exit;
		}
	}
}

mysqli_query($mysqli, "UPDATE m_users SET last_request = UTC_TIMESTAMP() WHERE id = $userId");

$db = makeDb($userId, $clientUserId);
$db->queryByUserId = true;

$requestChanges = json_decode($_POST['changes'], true);

$activity = $requestChanges['activity'];
unset($requestChanges['activity']);

$sharedObjects = $requestChanges['shared_objects'];
unset($requestChanges['shared_objects']);

$notifications = $requestChanges['notifications'];
unset($requestChanges['notifications']);

$permissions = $requestChanges['permissions'];
unset($requestChanges['permissions']);

if ($userId != $clientUserId) {
	if ($requestChanges['decisions']) {
		foreach ($requestChanges['decisions'] as $id => &$changes) {
			unset($changes['access']);
		}
		unset($changes);
	}
}

$db->execute((array)$requestChanges);

if ($clientId != 'Carl Sagan') {
	mysqli_query($mysqli, "DELETE FROM update_tokens WHERE id = $updateToken && user_id = $clientUserId");
}

$mapping = $db->mapping();

$responseChanges = array();

if ($activity) {
	foreach ($activity as $id => $entry) {
		if ($entry == 'deleted') continue;
		$table = modelNameToTableName($entry['object_type']);
		$objectId = $mapping[$table][$entry['object_id']];
		if (!$objectId) $objectId = $entry['object_id'];

		$objectType = mysqli_real_escape_string($mysqli, $entry['object_type']);
		$timestamp = mysqli_real_escape_string($mysqli, $entry['timestamp']);
		$type = mysqli_real_escape_string($mysqli, $entry['type']);

		$args = json_decode($entry['args'], true);

		foreach ($args as $i => &$arg) {
			if ($arg['model'] && $arg['id']) {
				$table = modelNameToTableName($arg['model']);
				if ($arg['id'][0] != 'G') {
					$arg['id'] = $mapping[$table][$arg['id']];
				}
			}
		}
		unset($arg);

		$args = json_encode($args);

		$escapedArgs = mysqli_real_escape_string($mysqli, $args);

		$db->storage->query("INSERT INTO activity SET 
			user_id = $userId,
			generator_id = $clientUserId,
			type = '$type',
			object_type = '$objectType',
			object_id = '$objectId',
			args = '$escapedArgs',
			`timestamp` = $timestamp");


		$globalId = 'G' . md5("$userId$entry[timestamp]$clientUserId$entry[object_type]$entry[object_id]$entry[type]$args");

		$mapping['activity'][$id] = $globalId;

		$entry += array('args' => $args, 'user_id' => $userId, 'generator_id' => $clientUserId, 'object_id' => $objectId);

		$responseChanges['activity'][$globalId] = $entry;
	}
}

if ($clientUserId == $userId) {
	if ($sharedObjects) {
		foreach ($sharedObjects as $id => $changes) {
			if (isset($changes['seen'])) {
				$seen = $changes['seen'] ? 1 : 0;
				$saneId = substr($id, 1);
				mysqli_query($mysqli, "UPDATE shared SET seen = $seen WHERE id = $saneId && with_user_id = $userId") or die(mysqli_error($mysqli));
				$responseChanges['shared_objects'][$id] = array('seen' => $changes['seen']);
			}
		}
	}
	if ($notifications) {
		foreach ($notifications as $id => $changes) {
			if (isset($changes['seen'])) {
				$seen = $changes['seen'] ? 1 : 0;
				$saneId = substr($id, 1);
				mysqli_query($mysqli, "UPDATE notifications SET seen = $seen WHERE id = $saneId && user_id = $userId") or die(mysqli_error($mysqli));
				$responseChanges['notifications'][$id] = array('seen' => $changes['seen']);
			}
		}
	}

	if ($permissions) {
		foreach ($permissions as $id => $changes) {
			$responseChanges['permissions'][$globalId] = $changes;
			if ($id[0] == 'G') {
				$processedId = processId($id);
				$permission = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT user_id,object FROM permissions WHERE id = $processedId && owner_id = $userId"));

				if ($changes == 'deleted') {
					mysqli_query($mysqli, "DELETE FROM permissions WHERE id = $processedId && owner_id = $userId");
					sendMessage($userId, 'alterPermission', array(
						'userId' => $userId,
						'action' => 'delete',
						'permission' => array('object' => $permission['object'], 'userId' => $permission['user_id'])
					));
				}
				else {
					$level = processInt($changes['level']);
					mysqli_query($mysqli, "UPDATE permissions SET level = $level WHERE id = $processedId && owner_id = $userId");
					sendMessage($userId, 'alterPermission', array(
						'userId' => $userId,
						'action' => 'update',
						'permission' => array('object' => $permission['object'], 'userId' => $permission['user_id'], 'level' => $changes['level'])
					));
				}
			}
			else {
				$permissionUserId = processId($changes['user_id']);
				$object = processString($changes['object']);
				$level = processInt($changes['level']);
				mysqli_query($mysqli, "INSERT INTO permissions SET owner_id = $userId, user_id = $permissionUserId, object = $object, level = $level");

				$globalId = dbIdToModelId(mysqli_insert_id($mysqli));
				$mapping['permissions'][$id] = $globalId;

				sendMessage($userId, 'alterPermission', array(
					'userId' => $userId,
					'action' => 'create',
					'permission' => array('object' => $changes['object'], 'userId' => substr($changes['user_id'], 1), 'level' => $changes['level'])
				));
			}
		}
	}
}

if ($db->changes) {
	$theChanges = array();
	foreach ($db->changes as $table => $changes) {
		foreach ($changes as $id => $changeType) {
			$finalId = $db->storage->finalId($table, $id);
			$theChanges[$table][$finalId] = $changeType == 'updated';

			if ($db->return[$table][$id]) {
				$return[$table][] = 'G' . $finalId;
			}
		}
	}

	foreach ($theChanges as $table => $records) {
		foreach ($records as $id => $changeType) {
			if (!$changeType) {
				$responseChanges[$table]['G' . $id] = 'deleted';
			}
			else {
				$retrievalList[$table][] = $id;
			}
		}
	}

	if ($retrievalList) {
		$retrievedRecords = $db->storage->get($retrievalList);
		foreach ($retrievalList as $table => $ids) {
			foreach ($ids as $id) {
				$record = $retrievedRecords[$table][$id];
				if (!$record) {
					throw new Exception("$table/$id doesn't exist in DB");
				}
				foreach ($record as $field => $value) {
					if ($value) {
						if ($db->isFk($table, $field)) {
							$record[$field] = "G$value";
						}
					}
				}
				$responseChanges[$table]["G$id"] = $record;
			}
		}

		foreach ($db->changes as $table => $records) {
			foreach ($records as $id => $record) {
				if ($id[0] != 'G') {
					onInsert($table, $finalId);
				}
			}
		}
	}
}

$response = array('status' => 'ok');
if ($responseChanges) {
	$response['changes'] = $responseChanges;
}

if ($return) {
	$response['return'] = $return;
}

if ($clientId != 'Carl Sagan') {
	$response['updateToken'] = newUpdateToken($clientUserId, $clientId);
}

if ($mapping) $response['mapping'] = $mapping;

echo json_encode($response);