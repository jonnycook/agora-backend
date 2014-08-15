<?php

class FeedbackCommentRepliesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'feedback_comment_replies'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'reply' => $storageRecord['reply'],
			'creator_id' => $storageRecord['creator_id'],
			'created_at' => $storageRecord['created_at'],
			'feedback_comment_id' => $this->db->tableHandler('feedback_comments')->storageLocationToModelId('feedback_comments', $storageRecord['feedback_comment_id']),
		);

		if ($storageRecord['parent_id']) {
			$modelRecord['parent_id'] = $this->db->tableHandler('feedback_comment_replies')->storageLocationToModelId('feedback_comment_replies', $storageRecord['parent_id']);
		}
		return $modelRecord;
	}
}

return array(
	'class' => FeedbackCommentRepliesTableHandler,
	'modelName' => 'FeedbackCommentReply',
	'model' => array(
		'referents' => array(
			'parent_id' => 'feedback_comment_replies',
			'feedback_comment_id' => 'feedback_comments',
		)
	)
);