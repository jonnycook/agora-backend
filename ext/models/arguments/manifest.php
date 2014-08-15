<?php

class ArgumentsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'arguments'; }
	public function storageTableHasUserIdField() { return true; }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'thought' => $storageRecord['thought'],
			'for' => $storageRecord['for'],
			'against' => $storageRecord['against'],
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
	'class' => ArgumentsTableHandler,
	'modelName' => 'Argument',
	'model' => array(
		'referents' => array(
			'element_id' => map,
		)
	),
);