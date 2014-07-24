<?php

class DescriptorsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'descriptors'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }


	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'element_id' && $value) {
			return $this->db->resolveIdToStorageId(map($this->modelRecord), $value);
		}

		return $value;
	} 

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'descriptor' => $storageRecord['descriptor'],
			'creator_id' => $storageRecord['creator_id'],
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
	'class' => DescriptorsTableHandler,
	'model' => array(
		'referents' => array(
			'element_id' => map,
		)
	),
);