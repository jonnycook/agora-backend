<?php

class ProductWatchesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'product_watches'; }
	public function storageTableHasUserIdField() { return true; }
	public function mapModelFieldToStorageField($field, $value) {
		switch ($field) {
			case 'product_id': return $this->db->resolveIdToStorageId('products', $value);
			case 'enable_threshold': return $value ? 1 : 0;
			case 'enabled': return $value ? 1 : 0;
			case 'enable_stock': return $value ? 1 : 0;
			case 'enable_increment': return $value ? 1 : 0;
			case 'seen': return $value ? 1 : 0;
			case 'watch_threshold': return $value === '' ? null : $value;
			case 'watch_increment': return $value === '' ? null : $value;
			case 'listing':
			case 'used':
			case 'refurbished':
			case 'new':
			case 'stock':
				return array();
		}

		return $value;
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$info = mysqli_fetch_assoc($this->query("SELECT listing,new,used,refurbished,stock FROM product_watch_info WHERE product_id = {$storageRecord['product_id']}"));

		return array(
			'product_id' => $this->db->tableHandler('products')->storageLocationToModelId('products', $storageRecord['product_id']),
			'watch_threshold' => $storageRecord['watch_threshold'],
			'watch_increment' => $storageRecord['watch_increment'],
			'reported_stock' => $storageRecord['reported_stock'],
			'reported_listing' => $storageRecord['reported_listing'],
			'reported_new' => $storageRecord['reported_new'],
			'reported_used' => $storageRecord['reported_used'],
			'reported_refurbished' => $storageRecord['reported_refurbished'],
			'initial_stock' => $storageRecord['initial_stock'],
			'initial_listing' => $storageRecord['initial_listing'],
			'initial_new' => $storageRecord['initial_new'],
			'initial_used' => $storageRecord['initial_used'],
			'initial_refurbished' => $storageRecord['initial_refurbished'],
			'enable_threshold' => $storageRecord['enable_threshold'],
			'enable_stock' => $storageRecord['enable_stock'],
			'enable_increment' => $storageRecord['enable_increment'],
			'watch_condition' => $storageRecord['watch_condition'],
			'seen' => $storageRecord['seen'],
			'index' => $storageRecord['index'],
			'enabled' => $storageRecord['enabled'],
			'state' => $storageRecord['state'],
			'listing' => $info['listing'],
			'new' => $info['new'],
			'used' => $info['used'],
			'refurbished' => $info['refurbished'],
			'stock' => $info['stock'],
		);
	}

	protected function insertValues() {
		$info = mysqli_fetch_assoc($this->query("SELECT listing,new,used,refurbished,stock FROM product_watch_info WHERE product_id = {$this->storageRecord['product_id']}"));
		return array(
			'reported_listing' => $info['listing'],
			'reported_new' => $info['new'],
			'reported_used' => $info['used'],
			'reported_refurbished' => $info['refurbished'],
			'reported_stock' => $info['stock'],
			'initial_listing' => $info['listing'],
			'initial_new' => $info['new'],
			'initial_used' => $info['used'],
			'initial_refurbished' => $info['refurbished'],
			'initial_stock' => $info['stock'],
			'state' => $info ? 1 : 0
		);
	}
}


return array(
	'class' => ProductWatchesTableHandler,
	'modelName' => 'ProductWatch',
	'model' => array(
		'returnInsert' => true,
		// 'distinct' => array('product_id', 'variant'),
		'referents' => array(
			'product_id' => 'products',
		),
	)
);