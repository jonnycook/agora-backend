<?php

class SessionsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'sessions'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'collapsed') {
			return $value ? 1 : 0;
		}
		else {
			return $value;
		}
	}
}

return array(
	'class' => SessionsTableHandler
);