<?php

class DB {
	public static $models;

	public static function isLocalId($id) {
		return $id[0] != 'G';
	}

	public static function convertGlobalId($id) {
		return substr($id, 1);
	}

	public function __construct($userId, $clientUserId, $storage) {
		$this->userId = $userId;
		$this->clientUserId = $clientUserId;
		$this->time = gmdate('Y-m-d H:i:s');
		$this->storage = $storage;
		$this->storage->db = $this;
	}

	public function model($name) {
		$modelManifest = $this->modelManifests[$name];
		if ($modelManifest) {
			return $modelManifest;
		}
		else {
			$manifestFilePath = __DIR__."/../models/$name/manifest.php";
			if (file_exists($manifestFilePath)) {
				return $this->modelManifests[$name] = include($manifestFilePath);
			}
			else {
				throw new Exception("No model named `$name`");
			}
		}
	}

	private function set($table, $id, $field, $value) {
		$this->storage->set($table, $id, $field, $value);
		$this->changes[$table][$id] = 'updated';
	}

	private function touch($table, $id) {
		$this->storage->touch($table, $id);
		$this->changes[$table][$id] = 'updated';
	}

	private function delete($table, $id) {
		$this->storage->delete($table, $id);
		$this->changes[$table][$id] = 'deleted';
	}

	private function localRecord($table, $localId) {
		$record = $this->input[$table][$localId];
		if ($record === null) {
			throw new Exception("$table/$localId is does not exist in input");
		}
		foreach ($record as $field => &$value) {
			if ($value) {
				$value = $this->transformValueIn($table, $record, $field, $value);
			}
		}
		unset($value);

		return $record;
	}

	public function id($table, $localId) {
		$id = $this->storage->id($table, $localId, $this->localRecord($table, $localId));
		if ($this->changes[$table][$id] == 'deleted') {
			return $this->storage->tempId($table, $localId);
		}
		else {
			return $id;
		}
	}

	public function isFk($table, $field) {
		return $this->model($table)['model']['referents'][$field];
	}

	public function referentId($table, $record, $field, $localId) {
		$referentTable = $this->model($table)['model']['referents'][$field];
		if (is_callable($referentTable)) {
			$referentTable = $referentTable($record);
		}

		return $this->id($referentTable, $localId);
	}

	private function transformValueIn($tableName, $record, $field, $value) {
		if ($this->isFk($tableName, $field)) {
			if ($this->model($tableName)['model']['types'][$field] == 'list') {
				$ids = explode(' ', $value);
				$newIds = array();
				foreach ($ids as $id) {
					if (self::isLocalId($id)) {
						$newIds[] = $this->referentId($tableName, $record, $field, $id);
					}
					else {
						$newIds[] = self::convertGlobalId($id);
					}
				}
				$value = implode(' ', $newIds);
			}
			else {
				if (self::isLocalId($value)) {
					$value = $this->referentId($tableName, $record, $field, $value);
				}
				else {
					$value = self::convertGlobalId($value);
				}
			}
		}
		return $value;
	}

	private function transformValueOut($tableName, $record, $field, $value) {
		if ($this->isFk($tableName, $field)) {
			if ($this->model($tableName)['model']['types'][$field] == 'list') {
				return implode(' ', array_map(function($id) { return "G$id"; }, explode(' ', $value)));
			}
			else {
				return "G$value";
			}
		}
		else {
			return $value;
		}
	}

	public function execute($input) {
		$this->input = $input;

		foreach ($this->input as $tableName => $records) {
			foreach ($records as $id => $record) {
				if ($record == 'deleted') {
					if (!self::isLocalId($id)) {
						$this->delete($tableName, self::convertGlobalId($id));
					}
				}
			}
		}
		foreach ($this->input as $tableName => $records) {
			foreach ($records as $id => $record) {
				if ($record != 'deleted') {
					if (self::isLocalId($id)) {
						$localId = $id;
						$id = $this->id($tableName, $localId);

						if ($this->model($tableName)['model']['returnInsert']) {
							$this->return[$tableName][$id] = true;
						}
					}
					else {
						$id = self::convertGlobalId($id);
					}

					$this->touch($tableName, $id);

					foreach ($record as $field => $value) {
						if ($value) {
							$value = $this->transformValueIn($tableName, $record, $field, $value);
						}
						$this->set($tableName, $id, $field, $value);
					}
				}
			}
		}

		$this->storage->save();
	}

	public function prepareData($records) {
		$data = array();

		foreach ($records as $table => $records) {
			foreach ($records as $id => $record) {
				foreach ($record as $field => $value) {
					if ($value) {
						$record[$field] = $this->transformValueOut($table, $record, $field, $value);
					}
				}
				$data[$table]["G$id"] = $record;
			}
		}
		return $data;
	}

	public function data() {
		return $this->prepareData($this->storage->getAllForUser());
	}

	public function mapping() {
		$mapping = array();
		foreach ($this->storage->mapping() as $tableName => $map) {
			foreach ($map as $localId => $globalId) {
				$mapping[$tableName][$localId] = "G$globalId";
			}
		}

		return $mapping;
	}
}
