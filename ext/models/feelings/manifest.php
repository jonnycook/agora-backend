<?php

class FeelingsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'feelings'; }
	public function storageTableHasUserIdField() { return true; }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'thought' => $storageRecord['thought'],
			'negative' => $storageRecord['negative'],
			'positive' => $storageRecord['positive'],
			'timestamp' => $storageRecord['timestamp'],
		);

		if ($storageRecord['element_type']) {
			$modelRecord['element_type'] = $storageRecord['element_type'];
			$modelRecord['element_id'] = $this->resolveValue('element_id', $modelRecord, $storageRecord['element_id']);
		}

		return $modelRecord;
	}
}

return array(
	'class' => FeelingsTableHandler,
	'modelName' => 'FeelingPage',
	'model' => array(
		'referents' => array(
			'element_id' => map,
		)
	),
);