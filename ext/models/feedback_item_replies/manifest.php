<?php

class FeedbackItemRepliesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'feedback_item_replies'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'reply' => $storageRecord['reply'],
			'creator_id' => $storageRecord['creator_id'],
			'created_at' => $storageRecord['created_at'],
			'feedback_item_id' => $this->db->tableHandler('feedback_items')->storageLocationToModelId('feedback_items', $storageRecord['feedback_item_id']),
		);

		if ($storageRecord['parent_id']) {
			$modelRecord['parent_id'] = $this->db->tableHandler('feedback_item_replies')->storageLocationToModelId('feedback_item_replies', $storageRecord['parent_id']);
		}
		return $modelRecord;
	}
}

return array(
	'class' => FeedbackItemRepliesTableHandler,
	'modelName' => 'FeedbackItemReply',
	'model' => array(
		'referents' => array(
			'parent_id' => 'feedback_item_replies',
			'feedback_item_id' => 'feedback_items',
		)
	)
);