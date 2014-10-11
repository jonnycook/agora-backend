<?php

class SessionElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'session_elements'; }
	public static function parentIdField() { return 'session_id'; }
	public static function parentTable() { return 'sessions'; }
}

return array(
	'class' => SessionElementsTableHandler,
	'modelName' => 'SessionElement',
	'model' => array(
		'referents' => array(
			'element_id' => 'map',
			'session_id' => 'sessions',
		)
	),
);
