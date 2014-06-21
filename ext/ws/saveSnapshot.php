<?php

require_once('header.php');

$userId = $_GET['userId'];

$tables = array(
	'm_bundles',
	'm_bundle_elements',
	'm_belts',
	'm_belt_elements',
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
	'm_users',
	'clients',
);
$data = array();

foreach ($tables as $table) {
	$result = mysqli_query($mysqli, "SELECT * FROM $table") or die(mysqli_error($mysqli));
	while ($row = mysqli_fetch_assoc($result)) {
		$data[$table][] = $row;
	}

	$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT `AUTO_INCREMENT`
		FROM  INFORMATION_SCHEMA.TABLES
		WHERE TABLE_SCHEMA = DATABASE()
		AND   TABLE_NAME   = '$table'"));

	$autoIncrement[$table] = $row['AUTO_INCREMENT'];
}


$snapshotsCol = mongoDb()->snapshots;
$snapshotsCol->insert(array(
	'_id' => intval($_GET['id']),
	'data' => $data,
	'autoIncrement' => $autoIncrement
));
