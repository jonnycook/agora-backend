<?php

class FeedbackPagesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'feedback_pages'; }
	public function storageTableHasUserIdField() { return true; }

	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'decision_id' && $value) {
			return $this->db->resolveIdToStorageId('decisions', $value);
		}
		else if ($field == 'referenced_products') {
			$productsIds = explode(' ', $value);
			$resolvedIds = array();
			foreach ($productIds as $productId) {
				$resolvedIds[] = $this->db->resolveIdToStorageId('products', $productId);
			}
			return implode(' ', $resolvedIds);
		}

		return $value;
	} 

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'question' => $storageRecord['question'],
			'feedback_type' => $storageRecord['feedback_type'],
			'positive' => $storageRecord['positive'],
			'timestamp' => $storageRecord['timestamp'],
		);

		if ($storageRecord['element_type']) {
			$elementTable = modelNameToTableName($storageRecord['element_type']);
			$modelRecord += array(
				'element_type' => $storageRecord['element_type'],
				'element_id' => $this->db->tableHandler($elementTable)->storageLocationToModelId($elementTable, $storageRecord['element_id']),
			);
		}

		return $modelRecord;
	}
}

return array(
	'class' => FeedbackPagesTableHandler,
	'model' => array(
		'types' => array(
			'referenced_products' => 'list'
		),
		'referents' => array(
			'decision_id' => 'decisions',
		)
	),
);