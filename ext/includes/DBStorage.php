<?php

class NoChangesException extends Exception {
	public function __toString() {
		return "No changes";
	}
}

abstract class TableHandler {
	public function __construct($userId) {
		$this->userId = $userId;
	}

	public static $onInsert;

	abstract public function execute($modelId, $modelRecord);
}

abstract class SqlTableHandler extends TableHandler {
	public function __construct($db, $userId, $clientUserId) {
		$this->db = $db;
		$this->userId = $userId;
		$this->clientUserId = $clientUserId;
	}

	protected static function query($sql) {
		return DBStorage::query($sql);
	}
	protected function idQueryPart($id) {
		global $mysqli;
		if (is_array($id)) {
			foreach ($id as $field => $value) {
				if (is_numeric($value)) {
					$query[] = "`$field` = $value";
				}
				else if (is_string($value)) {
					$query[] = "`$field` = '" . mysqli_real_escape_string($mysqli, $value) . '\'';
				}
				else {
					throw new Exception("Invalid type");
				}
			}
			$idQueryPart = implode(' && ', $query);
		}
		else {
			$idQueryPart = "id = $id";
		}

		if ($this->storageTableHasUserIdField()) {
			$idQueryPart .= " && user_id = $this->userId";
		}

		return $idQueryPart;
	}

	protected static function setQueryPart($values) {
		global $mysqli;
		$setQueryPart = array();
		foreach ($values as $field => $value) {
			if ($value === null) {
				$setQueryPart[] = "`$field` = NULL";
			}
			else {
				$setQueryPart[] = "`$field` = '" . mysqli_real_escape_string($mysqli, $value) . '\'';
			}
		}
		return implode(', ', $setQueryPart);
	}

	protected function insertValues() { return array(); }

	//
	protected function validate($storageTable, $storageRecord) { return true; }
	public static function modelTableName() { throw new Exception("Must be defined"); }
	protected function storageTableHasUserIdField() { return false; }
	protected function storageTableHasCreatorIdField() { return false; }
	public function storageLocationToModelId($storageTable, $storageId) { return $storageId; }

	// model to storage
	public static function unpackStorageLocationFromModelId($id) { return array(static::modelTableName(), $id); }
	public static function deriveStorageTableFromModelRecord($modelRecord) { return static::modelTableName(); }
	public function primaryStorageKeysFromModelRecord($modelRecord) { return null; }
	protected function mapModelFieldToStorageField($field, $value) {
		if ($value && $this->db->db->isFk(static::modelTableName(), $field)) {
			$modelConf = $this->db->db->model(static::modelTableName())['model'];
			$referentTable = $modelConf['referents'][$field];
			if (!$referentTable) throw new Exception("POOP");
			if (is_callable($referentTable)) {
				$referentTable = $referentTable($this->modelRecord);
			}
			if (!$referentTable) {
				throw new Exception("POOP");
			}

			if ($modelConf['types'][$field] == 'list') {
				$ids = explode(' ', $value);
				$newIds = array();
				foreach ($ids as $id) {
					$newIds[] = $this->db->resolveIdToStorageId($referentTable, $id);
				}
				$value = implode(' ' , $newIds);
			}
			else {
				$value = $this->db->resolveIdToStorageId($referentTable, $value);
			}
		}

		return $value;
	}

	protected function mapModelRecordToStorageRecord($onInsert = false) {
		$storageRecord = array();
		foreach ($this->modelRecord as $field => $value) {
			$mapped = $this->mapModelFieldToStorageField($field, $value);
			if ($mapped !== false) {
				if (is_array($mapped)) {
					$storageRecord = array_merge($storageRecord, $mapped);
				}
				else {
					$storageRecord[$field] = $mapped;
				}	
			}
		}

		if ($this->storageTableHasUserIdField() && $onInsert) {
			$storageRecord['user_id'] = $this->userId;
		}
		else {
			unset($storageRecord['user_id']);
		}

		if ($this->storageTableHasCreatorIdField() && $onInsert) {
			$storageRecord['creator_id'] = $this->clientUserId ? $this->clientUserId : 0;
		}
		else {
			unset($storageRecord['creator_id']);
		}

		return $storageRecord;
	}

	// storage to model
	public static function deriveModelIdFromStorageRecord($storageTable, $storageRecord) {
		return $storageRecord['id'];
	}
	protected static function mapStorageFieldToModelField($storageTable, $field, $value) { return $value; }
	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array();
		foreach ($storageRecord as $field => $value) {
			$mapped = static::mapStorageFieldToModelField($storageTable, $field, $value);
			if (is_array($mapped)) {
				$modelRecord = array_merge($modelRecord, $mapped);
			}
			else {
				$modelRecord[$field] = $mapped;
			}
		}
		return $modelRecord;
	}

	protected function mapStorageToModel($storageTable, $storageRecord) {
		$modelId = static::deriveModelIdFromStorageRecord($storageTable, $storageRecord);
		return array(
			static::modelTableName(),
			$modelId,
			$this->mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId)
		);
	}


	//
	public function modelIdForModelRecord($modelRecord) {
		global $mysqli;
		try {
			$associationKeys = $this->primaryStorageKeysFromModelRecord($modelRecord);
		}
		catch (NoChangesException $e) {
			return null;
		}

		if ($associationKeys) {
			if ($this->storageTableHasUserIdField()) {
				$associationKeys['user_id'] = $this->userId;
			}
			foreach ($associationKeys as $field => $value) {
				$query[] = "`$field` = '" . mysqli_real_escape_string($mysqli, $value) . '\'';
			}
			$query = implode(' && ', $query);
			// if ($select) {
			// 	$select = '`' . implode('`,`', (array)$select) . '`';
			// }
			// else {
			// 	$select = '1';
			// }

			$storageTable = $this->deriveStorageTableFromModelRecord($modelRecord);

			$result = static::query("SELECT * FROM `m_$storageTable` WHERE $query");
			$row = mysqli_fetch_assoc($result);

			if ($row) {
				return static::deriveModelIdFromStorageRecord($storageTable, $row);
			}
		}
	}

	public function getRecord($modelId) {
		list($dbTable, $dbId) = static::unpackStorageLocationFromModelId($modelId);
		$result = static::query("SELECT * FROM `m_$dbTable` WHERE id = $dbId");
		return mysqli_fetch_assoc($result);
	}

	public function retrieveModelRecordsFromStorageForModelIds($modelIds) {
		$dbRetrievalList = array();
		foreach ($modelIds as $modelId) {
			list($dbTable, $dbId) = static::unpackStorageLocationFromModelId($modelId);
			$dbRetrievalList[$dbTable][] = $dbId;
		}

		$retrievedRecords = array();
		foreach ($dbRetrievalList as $dbTable => $dbIds) {
			$condition = array();
			foreach ($dbIds as $dbId) {
				$condition[] = $this->idQueryPart($dbId);
			}
			$condition = '(' . implode(') || (', $condition) . ')';

			$result = static::query("SELECT * FROM `m_$dbTable` WHERE $condition");
			while ($row = mysqli_fetch_assoc($result)) {
				list($modelTable, $modelId, $modelRecord) = $this->mapStorageToModel($dbTable, $row);
				$retrievedRecords[$modelId] = $modelRecord;
			}
		}

		return $retrievedRecords;
	}


	// execute
	protected function executeDelete() {
		$idQueryPart = $this->idQueryPart($this->storageId);
		$this->query("DELETE FROM `m_$this->storageTable` WHERE $idQueryPart");
	}

	protected function executeInsert() {
		global $mysqli;
		$storageRecord = $this->storageRecord = $this->mapModelRecordToStorageRecord(true);
		$storageRecord = array_merge($storageRecord, $this->insertValues());

		// if ($this->validate($this->storageTable, $storageRecord)) {
			if (is_array($this->storageId)) {
				$storageRecord = array_merge($storageRecord, $this->storageId);
			}

			$setQueryPart = static::setQueryPart($storageRecord);
			$this->query("INSERT INTO `m_$this->storageTable` SET $setQueryPart");
			$storageRecord['id'] = mysqli_insert_id($mysqli);

			$onInsert = TableHandler::$onInsert;

			$modelId = static::deriveModelIdFromStorageRecord($this->storageTable, $storageRecord);
			$onInsert($this->modelTableName(), $this->modelId /* this is a temporary id */, $modelId);
			return $modelId;
		// }
	}

	protected function executeUpdate() {
		$values = $this->values = $this->mapModelRecordToStorageRecord();
		$setQueryPart = static::setQueryPart($values);
		if ($setQueryPart) {
			$idQueryPart = $this->idQueryPart($this->storageId);
			$this->query("UPDATE `m_$this->storageTable` SET $setQueryPart WHERE $idQueryPart");
		}
	}


	public function execute($modelId, $modelRecord) {
		// assert($modelId);
		// assert($modelRecord);
		if (!is_array($modelRecord) && $modelRecord != 'deleted') throw new Exception('Invalid record: ' . var_export($modelRecord, true));
		$this->modelId = $modelId;
		$this->modelRecord = $modelRecord;

		if ($this->modelRecord != 'deleted') {
			foreach ($this->modelRecord as $field => &$value) {
				if ($value) {
					if ($this->db->db->isFk(static::modelTableName(), $field)) {
						$modelConf = $this->db->db->model(static::modelTableName())['model'];
						$referentTable = $modelConf['referents'][$field];
						if (!$referentTable) throw new Exception("POOP");
						if (is_callable($referentTable)) {
							$referentTable = $referentTable($this->modelRecord);
						}
						if (!$referentTable) {
							throw new Exception("POOP");
						}

						if ($modelConf['types'][$field] == 'list') {
							$ids = explode(' ', $value);
							$newIds = array();
							foreach ($ids as $id) {
								$newIds[] = $this->db->resolveId($referentTable, $id);
							}
							$value = implode(' ' , $newIds);
						}
						else {
							$value = $this->db->resolveId($referentTable, $value);
						}
					}
				}
			}
			unset($value);
		}

		if (DBStorage::isATemporaryId($this->modelId)) {
			$this->storageTable = $this->deriveStorageTableFromModelRecord($this->modelRecord);
			return $this->executeInsert();
		}
		else {
			list($this->storageTable, $this->storageId) = static::unpackStorageLocationFromModelId($this->modelId);
			if ($this->modelRecord == 'deleted') {
				$this->executeDelete();
			}
			else {
				$this->executeUpdate();
			}
		}
	}

	protected function resolveValue($field, $modelRecord, $value) {
		if ($this->db->db->isFk(static::modelTableName(), $field)) {
			$modelConf = $this->db->db->model(static::modelTableName())['model'];
			$referentTable = $modelConf['referents'][$field];
			if (!$referentTable) throw new Exception("POOP");
			if (is_callable($referentTable)) {
				$referentTable = $referentTable($modelRecord);
			}
			if (!$referentTable) {
				throw new Exception("POOP");
			}

			if ($modelConf['types'][$field] == 'list') {
				$ids = explode(' ', $value);
				$newIds = array();
				foreach ($ids as $id) {
					$newIds[] = $this->db->tableHandler($referentTable)->storageLocationToModelId($referentTable, $id);
				}
				$value = implode(' ' , $newIds);
			}
			else {
				$value = $this->db->tableHandler($referentTable)->storageLocationToModelId($referentTable, $value);
			}
		}

		return $value;
	}
}


abstract class DBStorage {
	public static function isATemporaryId($id) {
		return $id[0] == 'T';
	}

	public function __construct($userId, $clientUserId) {
		$this->db = array();
		$this->userId = $userId;
		$this->clientUserId = $clientUserId;

		$db = $this;
		TableHandler::$onInsert = function($modelTable, $temporaryId, $modelId) use ($db) {
			$db->temporaryToModelId[$modelTable][$temporaryId] = $modelId;
		};
	}

	public static function query($sql) {
		global $mysqli;
		$result = mysqli_query($mysqli, $sql);
		if (!$result) {
			throw new Exception(mysqli_error($mysqli) . ": $sql");
		}
		// xdebug_print_function_stack();
		return $result;
	}

	public function modelIdToStorageTable($modelTable, $modelId) {
		$handler = $this->tableHandler($modelTable);

		if (self::isATemporaryId($modelId)) {
			return $handler->deriveStorageTableFromModelRecord($this->changes[$modelTable][$modelId]);
		}
		else {
			list($storageTable, $_) = $handler->unpackStorageLocationFromModelId($modelId);
			return $storageTable;
		}
	}

	public function resolveId($table, $id) {
		if (self::isATemporaryId($id)) {
			if ($modelId = $this->temporaryToModelId[$table][$id]) {
				return $modelId;
			}
			else {
				$handler = $this->tableHandler($table);
				if (isset($this->changes[$table][$id])) {
					return $handler->execute($id, $this->changes[$table][$id]);
				}
				else {
					throw new NoChangesException;
				}
			}
		}
		else {
			return $id;
		}
	}

	public function resolveIdToStorageId($table, $id) {
		$modelId = $this->resolveId($table, $id);
		list(, $storageId) = $this->tableHandler($table)->unpackStorageLocationFromModelId($modelId);
		if (!$storageId) {
			throw new Exception("Failed to resolve storage ID: $table $id");
		}
		return $storageId;
	}

	public function finalId($table, $id) {
		return self::isATemporaryId($id) ? $this->temporaryToModelId[$table][$id] : $id;
	}

	public function set($table, $id, $field, $value) {
		$this->changes[$table][$id][$field] = $value;
	}
	public function touch($table, $id) {
		$this->changes[$table][$id] = array();
	}

	public function getRecord($table, $id) {
		return $this->tableHandler($table)->getRecord($id);
	}

	public function get($retrievalList) {
		$retrievedRecords = array();
		foreach ($retrievalList as $table => $ids) {
			$retrievedRecords[$table] = $this->tableHandler($table)->retrieveModelRecordsFromStorageForModelIds($ids);
		}

		return $retrievedRecords;
	}

	public function delete($table, $id) {
		$this->changes[$table][$id] = 'deleted';
	}

	public function find($table, $record) {
		$handler = $this->tableHandler($table);
		return $handler->modelIdForModelRecord($record);
	}


	public function nextId($table) {
		return 'T' . (++ $this->ids[$table]);
	}

	public function tempId($table, $localId) {
		$id = $this->nextId($table);
		$this->mapping[$table][$localId] = $id;
		return $id;
	}

	public function id($table, $localId, $record) {
		$id = $this->mapping[$table][$localId];
		if (!$id) {
			$id = $this->find($table, $record);

			if ($id) {
				$this->mapping[$table][$localId] = $id;
			}
			else {
				$id = $this->tempId($table, $localId);
			}
		}
		return $id;
	}

	public function mapping() {
		$mapping = array();
		if ($this->mapping)	foreach ($this->mapping as $table => $ids) {
			foreach ($ids as $localId => $modelId) {
				if (self::isATemporaryId($modelId)) {
					$mapping[$table][$localId] = $this->temporaryToModelId[$table][$modelId];
				}
				else {
					$mapping[$table][$localId] = $modelId;
				}
			}
		}

		return $mapping;
	}

	public function tableHandler($table) {
		$class = $this->db->model($table)['class'];
		return new $class($this, $this->userId, $this->clientUserId);
		// $modelManifest = $this->modelManifests[$table];
		// if ($modelManifest) {
		// 	$class = $modelManifest['class']
		// 	return new $class($this, $this->userId, $this->clientUserId);
		// }
		// else {
		// 	$manifestFilePath = __DIR__."/../model/$table/manfiest.php";
		// 	if (file_exists($manifestFilePath)) {
		// 		$this->modelManifests[$table] = include($manifestFilePath);
		// 		return $this->tableHandler($table);
		// 	}
		// 	else {
		// 		throw new Exception("No table handler for table `$table`");
		// 	}
		// }
	}

	public function save() {
		if ($this->changes) {
			foreach ($this->changes as $table => $records) {
			 	foreach ($records as $id => $record) {
					if ($this->temporaryToModelId[$table][$id]) continue; // TODO: check if this is okay
					if ($record == 'deleted') {
						$this->tableHandler($table)->execute($id, $record);
					}
				}
			}
			foreach ($this->changes as $table => $records) {
			 	foreach ($records as $id => $record) {
					if ($this->temporaryToModelId[$table][$id]) continue; // TODO: check if this is okay
					if ($record != 'deleted') {
						$this->tableHandler($table)->execute($id, $record);
					}
				}
			}
		}
	}

	public abstract function getAllForUser();
}
