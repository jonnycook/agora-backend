<?php

class DecisionsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'decisions'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapModelFieldToStorageField($field, $value) {
		if ($field == 'shared') {
			if ($this->userId == $this->clientUserId) {
				return $value ? 1 : 0;				
			}
			else {
				return false;
			}
		}

		return parent::mapModelFieldToStorageField($field, $value);
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'list_id' => $this->db->tableHandler('lists')->storageLocationToModelId('lists', $storageRecord['list_id']),
			// 'dismissal_list_id' => $this->db->tableHandler('lists')->storageLocationToModelId('lists', $storageRecord['dismissal_list_id']),
			'display_options' => $storageRecord['display_options'],
			'share_title' => $storageRecord['share_title'],
			'title' => $storageRecord['title'],
			'share_message' => $storageRecord['share_message'],
			'shared' => $storageRecord['shared'],
			'creator_id' => $storageRecord['creator_id'],
			'access' => $storageRecord['access'],
			// 'element_type' => $storageRecord['element_type'],
			// 'element_id' => $this->db->tableHandler($elementTable)->storageLocationToModelId($elementTable, $storageRecord['element_id']),
		);

		if ($storageRecord['feedback_page_id']) {
			$modelRecord['feedback_page_id'] = $this->db->tableHandler('feedback_pages')->storageLocationToModelId('feedback_pages', $storageRecord['feedback_page_id']);
		}

		return $modelRecord;
	}
}


return array(
	'class' => DecisionsTableHandler,
	'modelName' => 'Decision',
	'model' => array(
		'referents' => array(
			// 'element_id' => map,
			'list_id' => 'lists',
			'feedback_page_id' => 'feedback_pages',
			// 'dismissal_list_id' => 'lists',
		)
	),
);