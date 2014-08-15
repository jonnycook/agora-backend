<?php

class ObjectReferencesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'object_references'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function storageRecordToModelRecordMap() {
		return array('object', 'object_user_id', 'creator_id');
	}
	
	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'object' => $storageRecord['object'],
			'object_user_id' => $storageRecord['object_user_id'],
			'creator_id' => $storageRecord['creator_id'],
		);
	}
}

return array(
	'class' => ObjectReferencesTableHandler,
	'modelName' => 'ObjectReference',
);