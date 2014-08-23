<?php

require_once('includes/DB.php');
require_once('includes/DBStorage.php');
require_once('includes/FileStorage.php');

abstract class ElementsTableHandler extends SqlTableHandler {
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }
	public function includeUserId() { return false; }
	static protected function parentIdField() {}
	static protected function parentTable() {}
	static protected function hasParent() { return true; }
	// protected function mapModelFieldToStorageField($field, $value) {
	// 	if ($field == 'element_id') {
	// 		if ($value) {
	// 			$id = $this->db->resolveIdToStorageId(map($this->modelRecord), $value);	
	// 			if (!$id) {
	// 				throw "not id $field $value";
	// 			}
	// 			return $id;
	// 		} 
	// 	}
	// 	else if ($field == static::parentIdField()) {
	// 		return $this->db->resolveIdToStorageId(static::parentTable(), $value);
	// 	}

	// 	return $value;
	// }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'index' => $storageRecord['index']
		);

		if (static::hasParent()) {
			$modelRecord[static::parentIdField()] = $this->db->tableHandler(static::parentTable())->storageLocationToModelId(static::parentTable(), $storageRecord[static::parentIdField()]);
		}

		if ($storageRecord['element_type']) {
			$elementTable = modelNameToTableName($storageRecord['element_type']);
			$modelRecord += array(
				'element_type' => $storageRecord['element_type'],
				'element_id' => $this->db->tableHandler($elementTable)->storageLocationToModelId($elementTable, $storageRecord['element_id']),
			);
		}

		if ($this->storageTableHasCreatorIdField()) {
			$modelRecord['creator_id'] = 'G' . $storageRecord['creator_id'];
		}

		if ($this->includeUserId()) {
			$modelRecord['user_id'] = 'G' . $storageRecord['user_id'];
		}

		return $modelRecord;
	}

	public function primaryStorageKeysFromModelRecord($modelRecord) {
		$keys = array(
			'element_type' => $modelRecord['element_type'],
			'element_id' => $this->db->resolveIdToStorageId(map($modelRecord), $modelRecord['element_id']),
		);

		if (static::hasParent()) {
			$keys[static::parentIdField()] = $this->db->resolveIdToStorageId(static::parentTable(), $modelRecord[static::parentIdField()]);
		}
		return $keys;
	}

	public static function deriveModelIdFromStorageRecord($storageTable, $storageRecord) {
		return $storageRecord['id'];
	}

	public static function unpackStorageLocationFromModelId($id) {
		return array(static::modelTableName(), $id);
	}

	// public function executeUpdate() {
	// 	parent::executeUpdate();

	// 	if ($id = $this->values['element_id']) {
	// 		$type = $this->values['element_type'];
	// 		if ($type == 'Decision') {
	// 			$row = mysqli_fetch_assoc($this->query("SELECT user_id FROM m_decisions WHERE id = $id"));
	// 			if ($row['user_id'] != $this->userId) {

	// 			}
	// 		}
	// 	}
	// }

	public function executeInsert() {
		$id = parent::executeInsert();
		if ($elId = $this->storageRecord['element_id']) {
			$type = $this->storageRecord['element_type'];
			if ($type == 'Decision' || $type == 'Bundle') {
				$table = modelNameToTableName($type);
				$row = mysqli_fetch_assoc($this->query("SELECT user_id FROM m_$table WHERE id = $elId"));
				if ($row['user_id'] != $this->userId) {
					global $mysqli;
					$data = $this->db->getData(array(
						'records' => array($type => array($elId))
					));
					foreach ($data as $table => $records) {
						mysqli_query($mysqli, "UPDATE m_$table SET user_id = $this->userId WHERE id IN (" . implode(',', array_keys($records)) . ')');
					}
				}
			}
		}

		return $id;
	}
}

function modelNameToTableName($type) {
	switch ($type) {
		case 'Product': return 'products';
		case 'ProductVariant': return 'product_variants';
		case 'Decision': return 'decisions';
		case 'Composite': return 'composites';
		case 'Belt': return 'belts';
		case 'Bundle': return 'bundles';
		case 'Session': return 'sessions';
		case 'List': return 'lists';
		case 'Descriptor': return 'descriptors';
		case 'DecisionElement': return 'decision_elements';
		case 'User': return 'users';
		case 'ObjectReference': return 'object_references';
		case 'FeedbackPage': return 'feedback_pages';
		case 'FeedbackItem': return 'feedback_items';
		case 'FeedbackItemReply': return 'feedback_item_replies';
		case 'FeedbackComment': return 'feedback_comments';
		case 'FeedbackCommentReply': return 'feedback_comment_replies';
		case 'FeedbackPageReferenceItem': return 'feedback_page_reference_items';
		case 'DecisionSuggestion': return 'decision_suggestions';
	}
	throw new Exception("No mapping for '$type'");
}

function map($record) {
	if (!$record['element_type']) {
		var_dump($record);
		throw new Exception('nope');
	}
	return modelNameToTableName($record['element_type']);
}

class Storage extends DBStorage {
	public function getData($args) {
		$modelRecords = array();
		$elementsQueue = (array)$args['elements'];
		$recordsQuery = (array)$args['records'];

		$productIds = array();

		if ($args['root']) {
			$table = modelNameToTableName($args['root']);
			$query = "SELECT * FROM m_$table WHERE user_id = $this->userId";
			$result = $this->query($query);
			$tableHandler = $this->tableHandler($table);

			while ($row = mysqli_fetch_assoc($result)) {
				$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
				$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
				if ($table == 'belts') {
					$elementsQueue[] = array('belt_elements', 'belt_id', $row['id']);
				}
			}
		}

		// var_dump($elementsQueue);
		// var_dump($modelRecords);

		do {
			foreach ($elementsQueue as $top) {
				if ($top == 'Root') {
					$table = 'root_elements';
					$query = "SELECT * FROM m_root_elements WHERE user_id = $this->userId";
				}
				// else if ($top == 'Collections') {
				// 	$table = 'collection_elements';
				// 	$query = "SELECT * FROM m_collection_elements WHERE user_id = $this->userId";
				// }
				else {
					list($table, $field, $id) = $top;
					$query = "SELECT * FROM m_$table WHERE $field = $id";
				}

				$result = $this->query($query);
				$tableHandler = $this->tableHandler($table);	
				while ($row = mysqli_fetch_assoc($result)) {
					$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
					$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
					if ($row['element_type']) {
						if ($row['element_type'] == 'Product') {
							$productIds[] = $row['element_id'];
						}
						else {
							$recordsQuery[$row['element_type']][] = $row['element_id'];
						}
					}

					if ($table == 'list_elements') {
						$recordsQuery['DecisionElement'][] = array('list_element_id', $row['id']);
					}
				}
			}

			unset($elementsQueue);

			// $allRecordsQuery = array();

			do {
				$newRecordsQuery = array();
				foreach ($recordsQuery as $modelName => $ids) {
					$idsByField = array();
					foreach ($ids as $id) {
						if (is_array($id)) {
							$idsByField[$id[0]][] = $id[1];
						}
						else {
							$idsByField['id'][] = $id;
						}
					}
					// $allRecordsQuery[$modelName] = array_merge((array)$allRecordsQuery[$modelName], $ids);

					$table = modelNameToTableName($modelName);

					foreach ($idsByField as $idField => $ids) {
						$query = "SELECT * FROM m_$table WHERE $idField IN (" . implode(', ', $ids) . ')';
						$result = $this->query($query);
						$tableHandler = $this->tableHandler($table);

						while ($row = mysqli_fetch_assoc($result)) {
							$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
							$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
							switch ($table) {
								case 'product_variants':
									$productIds[] = $row['product_id'];
									break;

								case 'decisions':
									$newRecordsQuery['List'][] = $row['list_id'];
									if ($row['feedback_page_id']) {
										$newRecordsQuery['FeedbackPage'][] = $row['feedback_page_id'];
									}

									$newRecordsQuery['FeedbackPage'][] = array('decision_id', $row['id']);
									$newRecordsQuery['DecisionSuggestion'][] = array('decision_id', $row['id']);
									break;

								case 'feedback_pages':
									$newRecordsQuery['FeedbackItem'][] = array('feedback_page_id', $row['id']);
									$newRecordsQuery['FeedbackComment'][] = array('feedback_page_id', $row['id']);
									$newRecordsQuery['FeedbackPageReferenceItem'][] = array('feedback_page_id', $row['id']);
									break;

								case 'feedback_page_reference_items':
									if ($row['type'] == 0) {
										$productIds[] = $row['item_id'];
									}
									else if ($row['type'] == 1) {
										$newRecordsQuery['ProductVariant'][] = $row['item_id'];
									}
									break;

								case 'decision_suggestions':
								case 'feedback_items':
								case 'feedback_comments':
									if ($row['element_type']) {
										if ($row['element_type'] == 'Product') {
											$productIds[] = $row['element_id'];
										}
										else {
											$newRecordsQuery[$row['element_type']][] = $row['element_id'];
										}
									}

									if ($table == 'feedback_items') {
										$newRecordsQuery['FeedbackItemReply'][] = array('feedback_item_id', $row['id']);
									}
									else if ($table == 'feedback_comments') {
										$newRecordsQuery['FeedbackCommentReply'][] = array('feedback_comment_id', $row['id']);
									}
									break;


								case 'descriptors':
									if ($row['element_id']) {
										$newRecordsQuery[$row['element_type']][] = $row['element_id'];
									}
									break;

								case 'competitive_lists':
									$elementsQueue[] = array('competitive_list_elements', 'competitive_list_id', $row['id']);
									break;

								case 'lists':
									$elementsQueue[] = array('list_elements', 'list_id', $row['id']);
									break;

								case 'belts':
									$elementsQueue[] = array('belt_elements', 'belt_id', $row['id']);
									break;

								case 'bundles':
									$elementsQueue[] = array('bundle_elements', 'bundle_id', $row['id']);
									break;

								case 'sessions':
									$elementsQueue[] = array('session_elements', 'session_id', $row['id']);
									break;

								case 'composites':
									$elementsQueue[] = array('composite_elements', 'composite_id', $row['id']);
									$elementsQueue[] = array('composite_slots', 'composite_id', $row['id']);
									break;
							}
						}
					}
					// var_dump($newRecordsQuery);
				}
				$recordsQuery = $newRecordsQuery;
			} while ($recordsQuery);
		} while ($elementsQueue);


		if ($args['auxiliary']) {
			$result = $this->query("SELECT * FROM m_product_watches WHERE user_id = $this->userId");
			$tableHandler = $this->tableHandler('product_watches');
			while ($row = mysqli_fetch_assoc($result)) {
				$modelId = $tableHandler->deriveModelIdFromStorageRecord('product_watches', $row);
				$modelRecords['product_watches'][$modelId] = $tableHandler->mapStorageRecordToModelRecord('product_watches', $row, $modelId);
				$productIds[] = $row['product_id'];
			}
		}

		if ($args['auxiliary']) {
			// foreach (array('data', 'feelings', 'arguments') as $table) {
			// 	$query = [];

			// 	foreach ($allRecordsQuery as $modelName => $ids) {
			// 		foreach ($ids as $id) {
			// 			$query[] = "element_type = '$modelName' && element_id = '$id' && user_id = $this->userId";
			// 		}
			// 	}

			// 	// var_dump($query);

			// 	if ($query) {
			// 		$query = '(' . implode(') || (', $query) . ')';

			// 		$result = $this->query("SELECT * FROM m_$table WHERE $query");
			// 		$tableHandler = $this->tableHandler($table);
			// 		while ($row = mysqli_fetch_assoc($result)) {
			// 			$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
			// 			$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
			// 		}
			// 	}
			// }

			$tables = array('data', 'feelings', 'arguments');
			foreach ($tables as $table) {
				$result = $this->query("SELECT * FROM m_$table WHERE user_id = $this->userId");
				$tableHandler = $this->tableHandler($table);
				while ($row = mysqli_fetch_assoc($result)) {
					if ($row['element_type'] == 'Product') {
						$productIds[] = $row['element_id'];
					}
					$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
					$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
				}
			}
		}


		// var_dump($this->userId);
		if ($args['products']) {
			$tableHandler = $this->tableHandler('products');

			if ($args['products'] == 'referenced') {
				if ($productIds) {
					$result = $this->query("SELECT * FROM m_products WHERE id IN (" . implode(', ', $productIds) . ')');
					while ($row = mysqli_fetch_assoc($result)) {
						// $allRecordsQuery['Product'][] = $row['id'];
						$modelId = $tableHandler->deriveModelIdFromStorageRecord('products', $row);
						$modelRecords['products'][$modelId] = $tableHandler->mapStorageRecordToModelRecord('products', $row, $modelId);
					}
				}
			} 
			else {
				$result = $this->query("SELECT * FROM user_products WHERE user_id = $this->userId");
				while ($row = mysqli_fetch_assoc($result)) {
					$row['id'] = $row['product_id'];
					// $allRecordsQuery['Product'][] = $row['id'];
					$modelId = $tableHandler->deriveModelIdFromStorageRecord('products', $row);
					$modelRecords['products'][$modelId] = $tableHandler->mapStorageRecordToModelRecord('products', $row, $modelId);
				}
			}
			
			// $result = $this->query("SELECT * FROM m_product_variants WHERE user_id = $this->userId");
			// $tableHandler = $this->tableHandler('product_variants');
			// while ($row = mysqli_fetch_assoc($result)) {
			// 	$allRecordsQuery['ProductVariant'][] = $row['id'];
			// 	$modelId = $tableHandler->deriveModelIdFromStorageRecord('product_variants', $row);
			// 	$modelRecords['product_variants'][$modelId] = $tableHandler->mapStorageRecordToModelRecord('product_variants', $row, $modelId);
			// }
		}

		if ($listElementIds) {
			$table = 'decision_elements';
			$query = "SELECT * FROM m_$table WHERE list_element_id IN (" . implode(', ', $listElementIds) . ')';
			$result = $this->query($query);
			$tableHandler = $this->tableHandler($table);
			while ($row = mysqli_fetch_assoc($result)) {
				$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
				$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
			}
		}

		// foreach ($allRecordsQuery as $table => &$ids) {
		// 	sort($ids);
		// }
		// unset($ids);

		if ($args['auxiliary']) {
			// foreach (array('data', 'feelings', 'arguments') as $table) {
			// 	$query = [];

			// 	foreach ($allRecordsQuery as $modelName => $ids) {
			// 		foreach ($ids as $id) {
			// 			$query[] = "element_type = '$modelName' && element_id = '$id' && user_id = $this->userId";
			// 		}
			// 	}

			// 	// var_dump($query);

			// 	if ($query) {
			// 		$query = '(' . implode(') || (', $query) . ')';

			// 		$result = $this->query("SELECT * FROM m_$table WHERE $query");
			// 		$tableHandler = $this->tableHandler($table);
			// 		while ($row = mysqli_fetch_assoc($result)) {
			// 			$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
			// 			$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
			// 		}
			// 	}
			// }

			$tables = array('data', 'feelings', 'arguments');
			foreach ($tables as $table) {
				$result = $this->query("SELECT * FROM m_$table WHERE user_id = $this->userId");
				$tableHandler = $this->tableHandler($table);
				while ($row = mysqli_fetch_assoc($result)) {
					$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
					$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
				}
			}
		}

		// var_dump($modelRecords);


		// $result = $this->query("SELECT * FROM m_feelings WHERE user_id = $this->userId");
		// $tableHandler = $this->tableHandler('feelings');
		// while ($row = mysqli_fetch_assoc($result)) {
		// 	$modelRecords['feelings'][$tableHandler->deriveModelIdFromStorageRecord('feelings', $row)] = $tableHandler->mapStorageRecordToModelRecord('feelings', $row);
		// }

		return $modelRecords;
	}

	public function getAllForUser() {
		return $this->getData(array(
			'root' => 'Belt',
			// 'elements' => 'Root',
			'records' => array('User' => array($this->userId)),
			'products' => true,
			'auxiliary' => true
		));
	}
}

function makeDb($userId, $clientUserId) {
	return new DB($userId, $clientUserId, new Storage($userId, $clientUserId));
}
