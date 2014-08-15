<?php

class UsersTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'users'; }
	public function mapModelFieldToStorageField($field, $value) {
		return $value;
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'name' => $storageRecord['name'],
			'tutorials' => $storageRecord['tutorials'],
			'user_colors' => $storageRecord['user_colors'],
			'tutorial_step' => $storageRecord['tutorial_step'],
			'email' => $storageRecord['email'],
			'alerts_email' => $storageRecord['alerts_email'],
		);
	}
}

return array(
	'class' => UsersTableHandler,
	'modelName' => 'User',
);