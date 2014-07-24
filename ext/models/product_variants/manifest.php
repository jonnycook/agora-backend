<?php

class ProductVariantsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'product_variants'; }
	public function storageTableHasUserIdField() { return false; }
	public function mapModelFieldToStorageField($field, $value) {
		if ($field == 'product_id') {
			return $this->db->resolveIdToStorageId('products', $value);
		}

		return $value;
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		// $elementTable = modelNameToTableName($storageRecord['element_type']);

		return array(
			'product_id' => $this->db->tableHandler('products')->storageLocationToModelId('products', $storageRecord['product_id']),
			'variant' => $storageRecord['variant'],
			'schema_version' => $storageRecord['schema_version'],
			// 'dismissal_list_id' => $this->db->tableHandler('lists')->storageLocationToModelId('lists', $storageRecord['dismissal_list_id']),
			// 'display_options' => $storageRecord['display_options'],
			// 'element_type' => $storageRecord['element_type'],
			// 'element_id' => $this->db->tableHandler($elementTable)->storageLocationToModelId($elementTable, $storageRecord['element_id']),
		);
	}
}

return array(
	'class' => ProductVariantsTableHandler,
	'model' => array(
		'distinct' => array('product_id', 'variant'),
		'referents' => array(
			'product_id' => 'products',
		),
	)
);