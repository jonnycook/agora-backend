<?php

class RootElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'root_elements'; }
	public static function hasParent() { return false; }
	public function includeUserId() { return true; }
	public function primaryStorageKeysFromModelRecord($modelRecord) { return null; }
}

return array(
	'class' => RootElementsTableHandler,
	'modelName' => 'RootElement',
	'model' => array(
		'referents' => array(
			'element_id' => 'map',
		)
	),
);