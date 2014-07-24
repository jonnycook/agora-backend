<?php

class DecisionElementsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'decision_elements'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return false; }

	public function mapModelFieldToStorageField($field, $value) {
		if ($field == 'decision_id') {
			return $this->db->resolveIdToStorageId('decisions', $value);
		}
		else if ($field == 'list_element_id') {
			return $this->db->resolveIdToStorageId('list_elements', $value);
		}
		else if ($field == 'selected') {
			return $value ? 1 : 0;
		}
		else if ($field == 'dismissed') {
			return $value ? 1 : 0;
		}

		return $value;
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'decision_id' => $this->db->tableHandler('decisions')->storageLocationToModelId('decisions', $storageRecord['decision_id']),
			'list_element_id' => $this->db->tableHandler('list_elements')->storageLocationToModelId('list_elements', $storageRecord['list_element_id']),
			'selected' => $storageRecord['selected'],
			'dismissed' => $storageRecord['dismissed'],
			'row' => $storageRecord['row'],
			'creator_id' => $storageRecord['creator_id'],
		);
	}
}


return array(
	'class' => DecisionElementsTableHandler,
	'model' => array(
		'referents' => array(
			'decision_id' => 'decisions',
			'list_element_id' => 'list_elements',
		)
	),
);