<?php

class FeedbackPagesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'feedback_pages'; }
	public function storageTableHasUserIdField() { return true; }

	// protected function mapModelFieldToStorageField($field, $value) {
	// 	if ($field == 'decision_id' && $value) {
	// 		return $this->db->resolveIdToStorageId('decisions', $value);
	// 	}
	// 	else if ($field == 'referenced_products') {
	// 		$productsIds = explode(' ', $value);
	// 		$resolvedIds = array();
	// 		foreach ($productIds as $productId) {
	// 			$resolvedIds[] = $this->db->resolveIdToStorageId('products', $productId);
	// 		}
	// 		return implode(' ', $resolvedIds);
	// 	}

	// 	return parent::mapModelFieldToStorageField($field, $value);
	// } 

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'question' => $storageRecord['question'],
			'feedback_type' => $storageRecord['feedback_type'],
		);

		$modelRecord['referenced_products'] = $this->resolveValue('referenced_products', $modelRecord, $storageRecord['referenced_products']);
		$modelRecord['decision_id'] = $this->resolveValue('decision_id', $modelRecord, $storageRecord['decision_id']);

		return $modelRecord;
	}
}

return array(
	'class' => FeedbackPagesTableHandler,
	'modelName' => 'FeedbackPage',
	'model' => array(
		'types' => array(
			'referenced_products' => 'list'
		),
		'referents' => array(
			'decision_id' => 'decisions',
			'referenced_products' => 'products',
		)
	),
);