<?php

class DecisionsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'decisions'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapModelFieldToStorageField($field, $value) {
		/*if ($field == 'element_id') {
			return $this->db->resolveIdToStorageId(map($this->modelRecord), $value);
		}
		else */if ($field == 'list_id') {
			return $this->db->resolveIdToStorageId('lists', $value);
		}
		else if ($field == 'shared') {
			if ($this->userId == $this->clientUserId) {
				return $value ? 1 : 0;				
			}
			else {
				return array();
			}
		}

		// else if ($field == 'dismissal_list_id') {
		// 	return $this->db->resolveIdToStorageId('lists', $value);
		// }

		return $value;
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		// $elementTable = modelNameToTableName($storageRecord['element_type']);

		return array(
			'list_id' => $this->db->tableHandler('lists')->storageLocationToModelId('lists', $storageRecord['list_id']),
			// 'dismissal_list_id' => $this->db->tableHandler('lists')->storageLocationToModelId('lists', $storageRecord['dismissal_list_id']),
			'display_options' => $storageRecord['display_options'],
			'share_title' => $storageRecord['share_title'],
			'share_message' => $storageRecord['share_message'],
			'shared' => $storageRecord['shared'],
			'creator_id' => $storageRecord['creator_id'],
			'access' => $storageRecord['access'],
			// 'element_type' => $storageRecord['element_type'],
			// 'element_id' => $this->db->tableHandler($elementTable)->storageLocationToModelId($elementTable, $storageRecord['element_id']),
		);
	}
}


return array(
	'class' => DecisionsTableHandler,
	'model' => array(
		'referents' => array(
			// 'element_id' => map,
			'list_id' => 'lists',
			// 'dismissal_list_id' => 'lists',
		)
	),
);