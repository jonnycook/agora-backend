<?php

class CompositesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'composites'; }
	public function storageTableHasUserIdField() { return true; }
}

return array(
	'class' => CompositesTableHandler,
	'modelName' => 'Composite',
);