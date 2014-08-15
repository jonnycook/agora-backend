<?php

class BundleElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'bundle_elements'; }
	public static function parentIdField() { return 'bundle_id'; }
	public static function parentTable() { return 'bundles'; }
}

return array(
	'class' => BundleElementsTableHandler,
	'modelName' => 'BundleElement',
	'model' => array(
		'referents' => array(
			'element_id' => map,
			'bundle_id' => 'bundles',
		)
	),

);