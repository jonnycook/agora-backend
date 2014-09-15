<?php

class BeltsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'belts'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapModelFieldToStorageField($field, $value) {
		if ($field == 'shared') {
			return $value ? 1 : 0;
		}

		return $value;
	}


	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'title' => $storageRecord['title'],
			'shared' => $storageRecord['shared'],
			'user_id' => 'G' . $storageRecord['user_id'],
			'creator_id' => $storageRecord['creator_id'],
		);
	}

}

return array(
	'class' => BeltsTableHandler,
	'modelName' => 'Belt',
);