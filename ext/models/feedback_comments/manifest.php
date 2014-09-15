<?php

class FeedbackCommentTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'feedback_comments'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'comment' => $storageRecord['comment'],
			'creator_id' => $storageRecord['creator_id'],
			'created_at' => $storageRecord['created_at'],
			'feedback_page_id' => $this->db->tableHandler('feedback_pages')->storageLocationToModelId('feedback_pages', $storageRecord['feedback_page_id']),
		);

		$modelRecord['element_type'] = $storageRecord['element_type'];
		$modelRecord['element_id'] = $this->resolveValue('element_id', $modelRecord, $storageRecord['element_id']);

		return $modelRecord;
	}
}

return array(
	'class' => FeedbackCommentTableHandler,
	'modelName' => 'FeedbackComment',
	'model' => array(
		'referents' => array(
			'element_id' => map,
			'feedback_page_id' => 'feedback_pages',
		)
	),
);