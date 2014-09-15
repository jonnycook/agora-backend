<?php

class SiteSettingsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'site_settings'; }
	public function storageTableHasUserIdField() { return true; }

	public function primaryStorageKeysFromModelRecord($modelRecord) {
		return array('user_id' => $this->userId, 'site' => $modelRecord['site']);
	}
}

return array(
	'site_settings' => SiteSettingsTableHandler,
	'modelName' => 'SiteSetting',
);