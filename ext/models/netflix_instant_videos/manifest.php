<?php

class NetflixInstantVideosTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'netflix_instant_videos'; }
	public function storageTableHasUserIdField() { return false; }
	public function mapModelFieldToStorageField($field, $value) {
		return $value;
	}

	public function primaryStorageKeysFromModelRecord($modelRecord) {
		return array('object_id' => $modelRecord['objectId']);
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'objectId' => $storageRecord['object_id'],
		);
	}
}


return array(
	'class' => NetflixInstantVideosTableHandler,
	'modelName' => 'NetflixInstantVideo',
	'model' => array(
		'distinct' => array('objectId')
	)
);