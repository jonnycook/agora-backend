<?php

class CompositeSlotsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'composite_slots'; }
	public function storageTableHasUserIdField() { return true; }


	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'element_id' && $value) {
			return $this->db->resolveIdToStorageId(map($this->modelRecord), $value);
		}
		// else if ($field == static::parentIdField()) {
		// 	return $this->db->resolveIdToStorageId(static::parentTable(), $value);
		// }

		return $value;
	} 

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'composite_id' => $storageRecord['composite_id'],
			'index' => $storageRecord['index'],
			'type' => $storageRecord['type'],
		);

		if ($storageRecord['element_type']) {
			$elementTable = modelNameToTableName($storageRecord['element_type']);
			$modelRecord += array(
				'element_type' => $storageRecord['element_type'],
				'element_id' => $this->db->tableHandler($elementTable)->storageLocationToModelId($elementTable, $storageRecord['element_id']),
			);
		}

		return $modelRecord;
	}


	public function primaryStorageKeysFromModelRecord($modelRecord) {
		return array(
			'composite_id' => $this->db->resolveIdToStorageId('composites', $modelRecord['composite_id']),
			'type' => $modelRecord['type'],
		);
	}

	public static function deriveModelIdFromStorageRecord($storageTable, $storageRecord) {
		return "$storageRecord[composite_id]:$storageRecord[type]";
	}

	public static function unpackStorageLocationFromModelId($id) {
		list($compositeId, $type) = explode(':', $id);
		return array(static::modelTableName(), array('composite_id' => $compositeId, 'type' => $type));
	}
}


return array(
	'class' => CompositeSlotsTableHandler,
	'modelName' => 'CompositeSlot',
	'model' => array(
		'referents' => array(
			'element_id' => map,
			'composite_id' => 'composites',
		),
	),
);