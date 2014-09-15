<?php

class DecisionSuggestionsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'decision_suggestions'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'comment' => $storageRecord['comment'],
			'creator_id' => $storageRecord['creator_id'],
			'state' => $storageRecord['state'],
			'decision_id' => $this->db->tableHandler('decisions')->storageLocationToModelId('decisions', $storageRecord['decision_id']),
		);

		if ($storageRecord['element_type']) {
			$modelRecord['element_type'] = $storageRecord['element_type'];
			$modelRecord['element_id'] = $this->resolveValue('element_id', $modelRecord, $storageRecord['element_id']);
		}

		return $modelRecord;
	}
}

return array(
	'class' => DecisionSuggestionsTableHandler,
	'modelName' => 'DecisionSuggestion',
	'model' => array(
		'referents' => array(
			'element_id' => map,
			'decision_id' => 'decisions',
		)
	),
);