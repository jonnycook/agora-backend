<?php

class BeltElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'belt_elements'; }
	public static function parentIdField() { return 'belt_id'; }
	public static function parentTable() { return 'belts'; }
}

return array(
	'class' => BeltElementsTableHandler,
	'model' => array(
		'referents' => array(
			'element_id' => map,
			'belt_id' => 'belts',
		)
	),
);