<?php

require_once('header.php');

$userId = getUserId($_GET['clientId']);

$db = makeDb($userId, null);

$toRetrieve = json_decode($_REQUEST['toRetrieve'], true);

$data = array();

foreach ($toRetrieve as $table => $ids) {
	if ($table == 'products' || $table == 'product_variants') {
		$records = $db->storage->tableHandler($table)->retrieveModelRecordsFromStorageForModelIds(array_map(function($id) {return substr($id, 1);}, $ids));
		foreach ($records as $id => $record) {
			$data[$table]["G$id"] = $record;
		}
	}
	else {
		if ($table == 'decisions') $model = 'Decision';
		else if ($table == 'lists') $model = 'List';
		else if ($table == 'bundles') $model = 'Bundle';
		$records = $db->prepareData($db->storage->getData(array(
			'records' => array($model => array_map(function($id) {return substr($id, 1);}, $ids))
		)));

		if ($records) {
			foreach ($records as $table => $tableRecords) {
				$data[$table] = array_merge((array)$data[$table], $tableRecords);
			}
		}
	}
}

echo json_encode($data);