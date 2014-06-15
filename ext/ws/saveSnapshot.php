<?php

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
	'clients',
);
$data = array();


$result = mysqli_query($mysqli, "SELECT * FROM m_users WHERE id = $userId") or die(mysqli_error($mysqli));
while ($row = mysqli_fetch_assoc($result)) {
	$data['m_users'][] = $row;
}

foreach ($tables as $table) {
	$result = mysqli_query($mysqli, "SELECT * FROM $table WHERE user_id = $userId") or die(mysqli_error($mysqli));
	while ($row = mysqli_fetch_assoc($result)) {
		$data[$table][] = $row;
	}
}

$productIds = array();
foreach ($data['user_products'] as $row) {
	$productIds[] = $row['product_id'];
}

$result = mysqli_query($mysqli, 'SELECT * FROM m_products WHERE id IN (' . implode(', ', $productIds) . ')');
while ($row = mysqli_fetch_assoc($result)) {
	$data['m_products'][] = $row;
}

$snapshotsCol = mongoDb()->snapshots;
$snapshotsCol->insert(array(
	'_id' => array('userId' => $userId, 'id' => $_GET['id']),
	'data' => $data
));
