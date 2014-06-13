<?php

class FileStorage {
	public function __construct($userId) {
		$this->db = array();
		$this->userId = $userId;
	}


	private function equal($table, $recordA, $recordB) {
		foreach (DB::$models[$table]['distinct'] as $field) {
			if ($recordA[$field] != $recordB[$field]) return false;
		}
		return true;
	}

	private function find($table, $record) {
		if (DB::$models[$table]['distinct']) {
			foreach ($this->records($table) as $id => $r) {
				if ($this->equal($table, $record, $r)) return $id;
			}
		}
	}

	private function records($table) {
		$this->load($table);
		return (array)$this->db[$table]['records'];
	}

	private function load($table, $forWriting = true) {
		if (!isset($this->db[$table])) {
			$fileName = "data/$this->userId/$table";

			if ($forWriting) {
				$fp = fopen($fileName, 'a+');
				flock($fp, LOCK_EX);
				
				$length = filesize($fileName);
				if ($length) {
					$data = json_decode(fread($fp, $length), true);
				}
				else {
					$data = array();
				}

				$this->fp[$table] = $fp;
			}
			else {
				$data = json_decode(file_get_contents($fileName), true);
			}

			$this->oldDb[$table] = $data;
			$this->db[$table] = $data;
		}
		return $this->db[$table];
	}

	public function set($table, $id, $field, $value) {
		$this->load($table);
		$this->db[$table]['records'][$id][$field] = $value;
	}

	public function get($tableName, $id) {
		$table = $this->load($tableName, false);
		return $table['records'][$id];
	}

	public function delete($table, $id) {
		$this->load($table);
		unset($this->db[$table]['records'][$id]);
	}

	private function nextId($table) {
		$this->load($table);
		return ++ $this->db[$table]['id'];
	}

	public function save() {
		foreach ($this->db as $tableName => $data) {
			$fp = $this->fp[$tableName];
			ftruncate($fp, 0);
			fwrite($fp, json_encode($data));
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}

	public function id($table, $localId, $record) {
		$id = $this->mapping[$table][$localId];
		if (!$id) {
			$id = $this->find($table, $record);

			if (!$id) {
				$id = $this->nextId($table);
			}

			$this->mapping[$table][$localId] = $id;
		}
		return $id;
	}

	public function mapping() {
		return (array)$this->mapping;
	}
}
