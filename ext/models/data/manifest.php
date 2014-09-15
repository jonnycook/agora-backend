<?php

class DataTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'data'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'url' => $storageRecord['url'],
			'text' => $storageRecord['text'],
			'type' => $storageRecord['type'],
			'comment' => $storageRecord['comment'],
			'title' => $storageRecord['title'],
			'creator_id' => $storageRecord['creator_id'],
		);

		if ($storageRecord['element_type']) {
			$modelRecord['element_type'] = $storageRecord['element_type'];
			$modelRecord['element_id'] = $this->resolveValue('element_id', $modelRecord, $storageRecord['element_id']);
		}

		return $modelRecord;
	}
}


return array(
	'class' => DataTableHandler,
	'modelName' => 'Datum',
	'model' => array(
		'referents' => array(
			'element_id' => map,
		)
	),
);