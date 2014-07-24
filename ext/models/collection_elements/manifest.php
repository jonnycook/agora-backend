<?php

class CollectionElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'collection_elements'; }
	public static function hasParent() { return false; }
}

return array(
	'class' => CollectionElementsTableHandler
);