<?php

require_once('header.php');

function deleteSharedEntry($userId, $shared) {
	global $mysqli;
	mysqli_query($mysqli, "DELETE FROM shared WHERE id = $shared[id]");

	sendMessage($userId, 'shared', array(
		'action' => 'delete',
		'userId' => $userId,
		'record' => array(
			'id' => $shared['id'],
			'user_id' => $shared['user_id'],
			'object' => $shared['object'],
			'with_user_id' => $shared['with_user_id']
		)
	));

	sendMessage($shared['with_user_id'], 'shared', array(
		'action' => 'delete',
		'userId' => $shared['with_user_id'],
		'record' => array(
			'id' => $shared['id'],
		)
	));
}

if ($_POST['userId'] && $_POST['object']) {
	$userId = mysqli_real_escape_string($mysqli, $_POST['userId']);
	$object = mysqli_real_escape_string($mysqli, $_POST['object']);
	$result = mysqli_query($mysqli, "SELECT * FROM shared WHERE object = '$object' && user_id = $userId");
	while ($row = mysqli_fetch_assoc($result)) {
		deleteSharedEntry($userId, $row);
	}

	if ($object != '/') {
		list($table, $objId) = explode('.', $object);
		if ($table == 'decisions') {
			mysqli_query($mysqli, "UPDATE m_decisions SET shared = 0 WHERE id = $objId");
			sendUpdate($userId, array(
				'decisions' => array("G$objId" => array('shared' => 0))
			));
		}
		else if ($table == 'belts') {
			mysqli_query($mysqli, "UPDATE m_belts SET shared = 0 WHERE id = $objId");
			sendUpdate($userId, array(
				'belts' => array("G$objId" => array('shared' => 0))
			));
		}
	}
}
else {
	$userId = $_GET['userId'];
	$id = mysqli_real_escape_string($mysqli, $_POST['id']);

	$shared = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT * FROM shared WHERE id = $id"));

	$row = mysqli_fetch_row(mysqli_query($mysqli, "SELECT COUNT(*) FROM shared WHERE object = '$shared[object]'"));
	if ($row[0] == 1) {
		$collaborators["G$userId.$shared[object].$userId"] = 'deleted';
	}
	$collaborators["G$userId.$shared[object].$shared[with_user_id]"] = 'deleted';

	sendMessage($userId, 'collaborators', array(
		'userId' => $userId,
		'object' => $shared['object'],
		'changes' => json_encode(array('collaborators' => $collaborators)),
	));

	deleteSharedEntry($userId, $shared);

	if ($shared['object'] != '/') {
		list($table, $objId) = explode('.', $shared['object']);
		if ($table == 'decisions') {
			if ($row[0] == 1) {
				mysqli_query($mysqli, "UPDATE m_decisions SET shared = 0 WHERE id = $objId");
				sendUpdate($userId, array(
					'decisions' => array("G$objId" => array('shared' => 0))
				));
			}
		}
		else if ($table == 'belts') {
			if ($row[0] == 1) {
				mysqli_query($mysqli, "UPDATE m_belts SET shared = 0 WHERE id = $objId");
				sendUpdate($userId, array(
					'belts' => array("G$objId" => array('shared' => 0))
				));
			}
		}
	}
}
