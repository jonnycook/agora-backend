<?php
define('ENV', 'TEST');

require_once('header.php');


$userId = $_GET['userId'];

$tables = array(
	'm_bundles',
	'm_bundle_elements',
	'm_data',
	'm_decisions',
	'm_decision_elements',
	'm_descriptors',
	'm_feelings',
	'm_lists',
	'm_list_elements',
	'm_object_references',
	'm_root_elements',
	'm_sessions',
	'm_session_elements',
	'user_products',
	'm_products',
	'clients',
);

mysqli_query($mysqli, 'TRUNCATE TABLE m_users');
foreach ($tables as $table) {
	mysqli_query($mysqli, "TRUNCATE TABLE $table");
}

$snapshotsCol = mongoDb()->snapshots;
$snapshot = $snapshotsCol->findOne(array('_id' => array('userId' => $userId, 'id' => $_GET['id'])));
mysqli_query($mysqli, 'SET SESSION sql_mode = "ANSI"') or die(mysqli_error($mysqli));
var_dump($snapshot);
foreach ($snapshot['data'] as $table => $rows) {
	foreach ($rows as $row) {
		$set = array();
		foreach ($row as $field => $value) {
			$set[] = "`$field`='" . mysqli_real_escape_string($mysqli, $value) . '\'';
		}
		mysqli_query($mysqli, "INSERT INTO $table SET " . implode(', ', $set)) or die(mysqli_error($mysqli));
	}
}