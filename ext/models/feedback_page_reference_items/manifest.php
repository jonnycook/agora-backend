<?php

class FeedbackPageReferenceItemsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'feedback_page_reference_items'; }
	public function storageTableHasUserIdField() { return true; }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			// 'image_url' => $storageRecord['image_url'],
			'type' => $storageRecord['type'],
			'index' => $storageRecord['index'],
			'feedback_page_id' => $this->db->tableHandler('feedback_pages')->storageLocationToModelId('feedback_pages', $storageRecord['feedback_page_id']),
		);

		$modelRecord['item_id'] = $this->resolveValue('item_id', $modelRecord, $storageRecord['item_id']);

		return $modelRecord;
	}
}

return array(
	'class' => FeedbackPageReferenceItemsTableHandler,
	'modelName' => 'FeedbackPageReferenceItem',
	'model' => array(
		'referents' => array(
			'item_id' => function($record) {
				switch ($record['type']) {
					case 0: return 'products';
					case 1: return 'product_variants';
					case 2: return null;
				}
			},
			'feedback_page_id' => 'feedback_pages',
		)
	),
);