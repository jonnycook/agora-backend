<?php

header('Access-Control-Allow-Origin: http://webapp.agora.dev');
header('Access-Control-Allow-Origin: http://agora.sh');
header('Access-Control-Allow-Credentials: true');

require_once(__DIR__.'/../includes/header.php');
require_once(__DIR__.'/../init.php');

function newUpdateToken($userId, $clientId) {
	global $mysqli;
	mysqli_query($mysqli, "INSERT INTO update_tokens SET user_id = $userId, client_id = '$clientId'") or die(mysqli_error($mysqli));
	return mysqli_insert_id($mysqli);
}


function getUserId($clientId) {
	global $mysqli;
	$row = mysqli_fetch_row(mysqli_query($mysqli, "SELECT user_id FROM clients WHERE client_id = '$clientId'"));

	if (!$row) {
		die('invalid client id');
	}
	return $row[0];
}

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


// if (ENV != 'TEST' && ENV != 'LOCAL_DEV')
// 	dbErrors();


function dbIdToModelId($id) {
	if ($id) {
		return "G$id";	
	}
}
