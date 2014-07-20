<?php

ini_set('html_errors', 0);

require_once('header.php');

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

if ($sharedObjects && $clientUserId == $userId) {
	foreach ($sharedObjects as $id => $changes) {
		if (isset($changes['seen'])) {
			$seen = $changes['seen'] ? 1 : 0;
			$saneId = substr($id, 1);
			mysqli_query($mysqli, "UPDATE shared SET seen = $seen WHERE id = $saneId && with_user_id = $userId") or die(mysqli_error($mysqli));
			$responseChanges['shared_objects'][$id] = array('seen' => $changes['seen']);
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