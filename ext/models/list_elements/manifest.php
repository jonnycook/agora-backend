<?php

class ListElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'list_elements'; }
	public static function parentIdField() { return 'list_id'; }
	public static function parentTable() { return 'lists'; }
}

return array(
	'class' => ListElementsTableHandler,
	'modelName' => 'ListElement',
	'model' => array(
		'referents' => array(
			'element_id' => map,
			'list_id' => 'lists',
		)
	),
);