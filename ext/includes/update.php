<?php

require_once(__DIR__ . '/header.php');
require_once(__DIR__.'/../init.php');

function dbErrors() {
	// Error handler 1
	function myErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
	{
	    if ($errno == E_NOTICE) {
	        // This error code is not included in error_reporting
	        return;
	    }

		$values = array_merge(getRequestParams(), array(
			'error_number' => $errno,
			'error_string' => $errstr,
			'error_file' => $errfile,
			'error_line' => $errline,
			'error_context' => serialize($errcontext),
			'function_stack' => serialize(xdebug_get_function_stack()),
		));

		insert($values, 'update_errors');
		echo 'error';

	    return true;
	}

	// set to the user defined error handler
	$old_error_handler = set_error_handler("myErrorHandler");

	function exceptionHandler($e) {
		$values = array_merge(getRequestParams(), array(
			'error_string' => $e->getMessage(),
			'error_file' => $e->getFile(),
			'error_line' => $e->getLine(),
			'function_stack' => serialize(xdebug_get_function_stack()),
		));

		insert($values, 'update_errors');
		echo 'error';

	    return true;
	}

	set_exception_handler('exceptionHandler');

	// Error handler 2
	error_reporting(E_ALL);
	ini_set('display_errors', 0);

	function shutdown() {
	    $isError = false;
	    if ($error = error_get_last()){
	        switch($error['type']){
	            case E_ERROR:
	            case E_CORE_ERROR:
	            case E_COMPILE_ERROR:
	            case E_USER_ERROR:
	                $isError = true;
	                break;
	        }
	    }

	    if ($isError) {
			$values = array_merge(getRequestParams(), array(
				'error_number' => $error['type'],
				'error_string' => $error['message'],
				'error_file' => $error['file'],
				'error_line' => $error['line'],
				'function_stack' => serialize(xdebug_get_function_stack()),
			));

			insert($values, 'update_errors');

			echo '"error"';
	    }
	}
	// register_shutdown_function('shutdown');
}


function getRequestParams() {
	global $db, $passedId, $lastTime, $params;

	$values = array(
		'user_id' => $db->userId,
		'ip' => remoteAddr(),
		'request_clientId' => $params['clientId'],
		'request_extVersion' => $params['extVersion'],
		'request_apiVersion' => $params['apiVersion'],
		'request_instanceId' => $params['instanceId'],
		'request_userId' => $passedId,
		'request_lastTime' => $lastTime ? $lastTime : '0000-00-00 00:00:00',
		'request_changes' => $params['changes'],
	);

	return $values;
}

function insert($values, $table) {
	global $mysqli;
	foreach ($values as $field => $value) {
		$setters[] = "`$field` = '" . mysqli_real_escape_string($mysqli, $value) . '\'';
	}

	$query = "INSERT INTO `$table` SET " . implode(',', $setters);
	mysqli_query($mysqli, $query) or die("$query: " . mysqli_error($mysqli));
}

function changes($db, $lastTime, $forceFromChanges = false) {
	$changes = array();
	$time = $lastTime;

	if ($db->userId || devEnv()) {
		// if ($lastTime) {
		// 	$result = dynamoDbClient()->getItem(array(
		// 		'TableName' => dynamoDbTableName('settings'),
		// 		'Key' => array(
		// 			'HashKeyElement' => array('S' => 'ChangesLastCleared')
		// 		)
		// 	));
		// 	if ($result['Item'] && $result['Item']['value']['S'] >= $lastTime) $lastTime = null;
		// }

		if ($lastTime || $forceFromChanges) {
			$changesCol = mongoClient()->{MONGO_DB}->changes;
			$mongoId = array('_id' => array(
				'storeId' => array('userId' => intval($db->userId)),
				'clientId' => $db->clientId,
				// 'test' => 2
			));

			// var_dump($mongoId);


			// $result = $changesCol->update($mongoId, array('$unset' => array('lastChecked' => time())));
			// var_dump($result);
			// $result = $changesCol->findOne($mongoId);
			// var_dump($result);
			$result = $changesCol->findAndModify($mongoId, array('$unset' => array('changes' => ''), '$set' => array('lastChecked' => time())), array('changes' => 1, 'lastChanged' => 1, 'lastChecked' => 1, '_id' => 0));
			// var_dump($result);
			if ($result) {
				$time = $result['lastChanged'] ? $result['lastChanged'] : 1;

				$theChanges = $result['changes'];

				if ($theChanges) {
					foreach ($theChanges as $table => $records) {
						foreach ($records as $id => $changeType) {
							if (!$changeType) {
								$changes[$table]['G' . $id] = 'deleted';
							}
							else {
								$retrievalList[$table][] = $id;
							}
						}
					}

					// $result = mysql_query("SELECT * FROM changes WHERE user_id = $db->userId" . ($lastTime ? " && `timestamp` > '$lastTime'" : ' && !deleted'));
					
					// $retrievalList = array();
					// while ($row = mysqli_fetch_assoc($result)) {
					// 	if ($row['timestamp'] > $time) $time = $row['timestamp'];

					// 	if ($row['deleted']) {
					// 		$changes[$row['table']]['G' . $row['rid']] = 'deleted';
					// 	}
					// 	else {
					// 		$retrievalList[$row['table']][] = $row['rid'];
					// 	}
					// }

					if ($retrievalList) {
						$retrievedRecords = $db->storage->get($retrievalList);

						foreach ($retrievalList as $table => $ids) {
							foreach ($ids as $id) {
								$record = $retrievedRecords[$table][$id];
								if (!$record) {
									throw  new Exception("$table/$id doesn't exist in DB");
								}
								foreach ($record as $field => $value) {
									if ($value) {
										if ($db->isFk($table, $field)) {
											$record[$field] = "G$value";
										}
									}
								}
								$changes[$table]["G$id"] = $record;
							}
						}
					}
				}
			}
			else {
				$time = gmdate('Y-m-d H:i:s');
				try {
					$storesCol = mongoDb()->changes->insert(array(
						'_id' => array(
							'storeId' => array('userId' => intval($db->userId)),
							'clientId' => $db->clientId,
						),
						'storeId' => array('userId' => intval($db->userId)),
						'clientId' => $db->clientId,
						'lastChecked' => time()
					));
				}

				catch (MongoDuplicateKeyException $e) {
					
				}
				// $storesCol = mongoClient()->{MONGO_DB}->stores;
				// $storesCol->update(
				// 	array('_id' => array('userId' => $db->userId)), 
				// 	array(
				// 		'$push' => array('subscribers' => array('clientId' => $db->clientId)),
				// 	),
				// 	array('upsert' => true)
				// );
			}
		}
		else {
			try {
				$storesCol = mongoDb()->changes->insert(array(
					'_id' => array(
						'storeId' => array('userId' => intval($db->userId)),
						'clientId' => $db->clientId,
					),
					'storeId' => array('userId' => intval($db->userId)),
					'clientId' => $db->clientId,
					'lastChecked' => time()
				));
			}
			catch (MongoDuplicateKeyException $e) {

			}

			$changes = $db->data();

			$time = gmdate('Y-m-d H:i:s');
			$fullResultSet = true;
		}
	}

	return array($changes, $time, (bool)$fullResultSet);
}


function update($args) {
	// return array(
	// 	'status' => 'down',
	// 	'message' => 'Down for maintainence!',
	// 	// 'request' => $requestChanges,
	// 	// 'time' => $db->time,
	// 	// 'userId' => intval($db->userId),
	// 	// 'clientId' => $db->clientId,
	// 	// 'updateInterval' => 20000,
	// 	// 'track' => (bool)$user['track'],
	// 	// 'allData' => $allData,
	// 	// 'domain' => 'test.agora.sh'
	// );

	global $mysqli;
	global $db, $passedId, $lastTime, $params;
	$params = $args;
	$loggedInId = userId();
	$passedId = $args['userId'];

	if (!$loggedInId) return 'not signed in';
	// if ($passedId && $passedId != $loggedInId) return 'mismatched user ids';


	$lastTime = $args['lastTime'];
	$requestChanges = json_decode($args['changes'], true);

	if ($passedId) {
		$storeUserId = $passedId;
	}
	else {
		$storeUserId = $loggedInId;
	}

	$extVersion = mysqli_real_escape_string($mysqli, $args['extVersion']);


	$clientId = $args['clientId'];
	if (!$clientId) {
		$clientId = md5(rand());
		mysqli_query($mysqli, "INSERT INTO clients SET client_id = '$clientId', user_id = $loggedInId, created_at = UTC_TIMESTAMP(), last_seen_at = UTC_TIMESTAMP(), `version` = '$extVersion'");
	}
	else {
		$clientId = mysqli_real_escape_string($mysqli, $clientId);
		mysqli_query($mysqli, "UPDATE clients SET last_seen_at = UTC_TIMESTAMP(), `version` = '$extVersion' WHERE user_id = $loggedInId && client_id = '$clientId'");
		if (!mysqli_affected_rows($mysqli)) {
			mysqli_query($mysqli, "INSERT INTO clients SET client_id = '$clientId', user_id = $loggedInId, created_at = UTC_TIMESTAMP(), last_seen_at = UTC_TIMESTAMP(), `version` = '$extVersion'");
		}
	}

	if ($args['changes'] != '{}') {
		mysqli_query($mysqli, "UPDATE clients SET last_update_at = UTC_TIMESTAMP() WHERE user_id = $loggedInId && client_id = '$clientId'");
	}

	$db = makeDb($storeUserId, $clientId);


	$user = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT track FROM m_users WHERE id = $loggedInId"));
	mysqli_query($mysqli, "UPDATE m_users SET last_request = '$db->time', ext_version = '$extVersion' WHERE id = $loggedInId");

	$db->execute((array)$requestChanges);

	if ($db->changes) {
		$changesCol = mongoDb()->changes;
		$modification = array();

		foreach ($db->changes as $table => $changes) {
			foreach ($changes as $id => $changeType) {
				$id = $db->storage->finalId($table, $id);
				$modification["changes.$table.$id"] = $changeType == 'updated';
			}
		}

		$modification['lastChanged'] = $db->time;
		$changesCol->update(array('storeId' => array('userId' => intval($db->userId)), 'clientId' => array('$ne' => $db->clientId)), array('$set' => $modification), array('multiple' => true));
	}

	list($changes, $time, $fullResultSet) = changes($db, $passedId != $db->userId ? null : $lastTime);

	$response = mysqli_query($mysqli, "SELECT id,command FROM client_commands WHERE stage = 0 && client_id = '$clientId'");
	while ($row = mysqli_fetch_assoc($response)) {
		$commands[] = array(
			'id' => $row['id'],
			'command' => $row['command'],
		);
	}
	if ($commands) {
		mysqli_query($mysqli, "UPDATE client_commands SET stage = 1 WHERE id IN (" . implode(',', array_map(function($i) { return $i['id']; }, $commands))  . ')');
	}

	$response = array(
		'status' => 'ok',
		// 'message' => 'Down for maintainence!',
		// 'request' => $requestChanges,
		'time' => $db->time,
		'userId' => intval($db->userId),
		'clientId' => $db->clientId,
		'updateInterval' => 20000,
		'track' => (bool)$user['track'],
		// 'allData' => $allData,
		// 'domain' => 'test.agora.sh'
	);

	if ($commands) {
		$response['commands'] = $commands;
	}

	if ($changes) {
		$response['changes'] = $changes;
	}

	$mapping = $db->mapping();
	if ($mapping) {
		$response['mapping'] = $mapping;
	}

	if ($fullResultSet) {
		$response['full'] = true;
	}

	$values = array_merge(getRequestParams(), array(
		'response_changes' => json_encode($response['changes']),
		'response_time' => $response['time'],
		'response_mapping' => json_encode($response['mapping']),
		'response_userId' => $response['userId'],
		'response_full' => (int)$response['full'],
	));
	insert($values, 'update_logs');


	// header('Content-Type: application/json');
	return $response;
}

