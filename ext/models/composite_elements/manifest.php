<?php

class CompositeElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'composite_elements'; }
	public static function parentIdField() { return 'composite_id'; }
	public static function parentTable() { return 'composites'; }
}

return array(
	'class' => CompositeElementsTableHandler,
	'model' => array(
		'referents' => array(
			'element_id' => map,
			'composite_id' => 'bundles',
		)
	),
);