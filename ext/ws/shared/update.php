<?php

require_once('header.php');

$userId = $_GET['userId'];
$object = mysqli_real_escape_string($mysqli, $_POST['object']);

if ($object != '/') {
	list($table, $id) = explode('.', $object);
	if ($table == 'decisions') {
		$title = '"' . mysqli_real_escape_string($mysqli, $_POST['title']) . '"';

		// mysqli_query($mysqli, "UPDATE m_decisions SET share_title = '$title' WHERE id = $id");
		mysqli_query($mysqli, "UPDATE shared SET title = $title WHERE object = '$object' && user_id = $userId");

		sendUpdate($userId, array(
			'decisions' => array("G$id" => array('share_title' => $_POST['title'], 'share_message' => $_POST['message']))
		));

		$result = mysqli_query($mysqli, "SELECT * FROM shared WHERE object = '$object' && user_id = $userId");
		while ($row = mysqli_fetch_assoc($result)) {
			$record = array(
				'id' => $row['id'],
				'title' => $_POST['title'],
			);

			sendMessage($userId, 'shared', array(
				'action' => 'update',
				'userId' => $userId,
				'record' => $record,
			));
			sendMessage($row['with_user_id'], 'shared', array(
				'action' => 'update',
				'userId' => $row['with_user_id'],
				'record' => $record,
			));
		}
	}
}
