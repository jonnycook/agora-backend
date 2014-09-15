<?php

class BundlesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'bundles'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }
}

return array(
	'class' => BundlesTableHandler,
	'modelName' => 'Bundle',
	'model' => array(
		'referents' => array(
			'element_id' => map,
			'session_id' => 'sessions',
		)
	),
);