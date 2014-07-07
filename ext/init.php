<?php

require_once('includes/DB.php');
require_once('includes/DBStorage.php');
require_once('includes/FileStorage.php');

abstract class ElementsTableHandler extends SqlTableHandler {
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }
	public function includeUserId() { return false; }
	static protected function parentIdField() {}
	static protected function parentTable() {}
	static protected function hasParent() { return true; }
	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'element_id') {
			if ($value) {
				$id = $this->db->resolveIdToStorageId(map($this->modelRecord), $value);	
				if (!$id) {
					throw "not id $field $value";
				}
				return $id;
			} 
		}
		else if ($field == static::parentIdField()) {
			return $this->db->resolveIdToStorageId(static::parentTable(), $value);
		}

		return $value;
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'index' => $storageRecord['index']
		);

		if (static::hasParent()) {
			$modelRecord[static::parentIdField()] = $this->db->tableHandler(static::parentTable())->storageLocationToModelId(static::parentTable(), $storageRecord[static::parentIdField()]);
		}

		if ($storageRecord['element_type']) {
			$elementTable = modelNameToTableName($storageRecord['element_type']);
			$modelRecord += array(
				'element_type' => $storageRecord['element_type'],
				'element_id' => $this->db->tableHandler($elementTable)->storageLocationToModelId($elementTable, $storageRecord['element_id']),
			);
		}

		if ($this->storageTableHasCreatorIdField()) {
			$modelRecord['creator_id'] = 'G' . $storageRecord['creator_id'];
		}

		if ($this->includeUserId()) {
			$modelRecord['user_id'] = 'G' . $storageRecord['user_id'];
		}

		return $modelRecord;
	}

	public function primaryStorageKeysFromModelRecord($modelRecord) {
		$keys = array(
			'element_type' => $modelRecord['element_type'],
			'element_id' => $this->db->resolveIdToStorageId(map($modelRecord), $modelRecord['element_id']),
		);

		if (static::hasParent()) {
			$keys[static::parentIdField()] = $this->db->resolveIdToStorageId(static::parentTable(), $modelRecord[static::parentIdField()]);
		}
		return $keys;
	}

	public static function deriveModelIdFromStorageRecord($storageTable, $storageRecord) {
		return $storageRecord['id'];
	}

	public static function unpackStorageLocationFromModelId($id) {
		return array(static::modelTableName(), $id);
	}

	// public function executeUpdate() {
	// 	parent::executeUpdate();

	// 	if ($id = $this->values['element_id']) {
	// 		$type = $this->values['element_type'];
	// 		if ($type == 'Decision') {
	// 			$row = mysqli_fetch_assoc($this->query("SELECT user_id FROM m_decisions WHERE id = $id"));
	// 			if ($row['user_id'] != $this->userId) {

	// 			}
	// 		}
	// 	}
	// }

	public function executeInsert() {
		$id = parent::executeInsert();
		if ($elId = $this->storageRecord['element_id']) {
			$type = $this->storageRecord['element_type'];
			if ($type == 'Decision' || $type == 'Bundle') {
				$table = modelNameToTableName($type);
				$row = mysqli_fetch_assoc($this->query("SELECT user_id FROM m_$table WHERE id = $elId"));
				if ($row['user_id'] != $this->userId) {
					global $mysqli;
					$data = $this->db->getData(array(
						'records' => array($type => array($elId))
					));
					foreach ($data as $table => $records) {
						mysqli_query($mysqli, "UPDATE m_$table SET user_id = $this->userId WHERE id IN (" . implode(',', array_keys($records)) . ')');
					}
				}
			}
		}

		return $id;
	}
}

function modelNameToTableName($type) {
	switch ($type) {
		case 'Product': return 'products';
		case 'ProductVariant': return 'product_variants';
		case 'Decision': return 'decisions';
		case 'Composite': return 'composites';
		case 'Belt': return 'belts';
		case 'Bundle': return 'bundles';
		case 'Session': return 'sessions';
		case 'List': return 'lists';
		case 'Descriptor': return 'descriptors';
		case 'DecisionElement': return 'decision_elements';
		case 'User': return 'users';
		case 'ObjectReference': return 'object_references';
	}
	throw new Exception("No mapping for '$type'");
}

function map($record) {
	if (!$record['element_type']) {
		var_dump($record);
		throw new Exception('nope');
	}
	return modelNameToTableName($record['element_type']);
}

DB::$models = array(
	'products' => array(
		'distinct' => array('siteName', 'productSid')
	),
	'product_variants' => array(
		'distinct' => array('product_id', 'variant'),
		'referents' => array(
			'product_id' => 'products',
		),
	),
	'root_elements' => array(
		'referents' => array(
			'element_id' => map,
		)
	),
	// 'collection_elements' => array(
	// 	'referents' => array(
	// 		'element_id' => map,
	// 	)
	// ),
	'session_elements' => array(
		'referents' => array(
			'element_id' => map,
			'session_id' => 'sessions',
		)
	),
	'bundle_elements' => array(
		'referents' => array(
			'element_id' => map,
			'bundle_id' => 'bundles',
		)
	),
	'belt_elements' => array(
		'referents' => array(
			'element_id' => map,
			'belt_id' => 'belts',
		)
	),
	'composite_elements' => array(
		'referents' => array(
			'element_id' => map,
			'composite_id' => 'bundles',
		)
	),
	'composite_slots' => array(
		'referents' => array(
			'element_id' => map,
			'composite_id' => 'composites',
		),
	),
	'decisions' => array(
		'referents' => array(
			// 'element_id' => map,
			'list_id' => 'lists',
			// 'dismissal_list_id' => 'lists',
		)
	),
	'decision_elements' => array(
		'referents' => array(
			'decision_id' => 'decisions',
			'list_element_id' => 'list_elements',
		)
	),
	'list_elements' => array(
		'referents' => array(
			'element_id' => map,
			'list_id' => 'lists',
		)
	),
	// 'competitive_list_elements' => array(
	// 	'referents' => array(
	// 		'element_id' => map,
	// 		'competitive_list_id' => 'competitive_lists',
	// 	)
	// ),
	'data' => array(
		'referents' => array(
			'element_id' => map,
		)
	),
	'feelings' => array(
		'referents' => array(
			'element_id' => map,
		)
	),
	'arguments' => array(
		'referents' => array(
			'element_id' => map,
		)
	),
	'descriptors' => array(
		'referents' => array(
			'element_id' => map,
		)
	),
);

class ProductsTableHandler extends SqlTableHandler {
	// private static function dynamoTableName() {
	// 	return dynamoDbTableName('products');
	// }

	// private static function dynamoDbClient() {
	// 	return dynamoDbClient();
	// }

	public static function modelSiteNameToStorageSiteId($modelSiteName) {
		if ($modelSiteName == 'General') {
			return null;
		}
		else {
			if (ENV == 'LOCAL_DEV' || ENV == 'LINODE_DEV' || ENV == 'TEST') {
				return $modelSiteName;
			}
			else {
				return Site::siteForName($modelSiteName)->id;
			}
		}
	}

	public static function storageSiteIdToModelSiteName($storageSiteId) {
		if (ENV == 'LOCAL_DEV' || ENV == 'LINODE_DEV' || ENV == 'TEST') {
			return $storageSiteId;
		}
		else {
			return Site::siteForId($storageSiteId)->name;
		}
	}

	public static function modelTableName() { return 'products'; }


	public function storageTableHasUserIdField() { return false; }


	// model to storage
	public static function unpackStorageLocationFromModelId($id) {
		$parts = explode(':', $id);
		return array('products', $parts[1]);
	}

	public static function deriveStorageTableFromModelRecord($modelRecord) {
		return 'products';
	}

	protected function mapModelRecordToStorageRecord($onInsert = false) {
		// $this->onInsert = $onInsert;

		if ($onInsert) {
			$this->type = $this->modelRecord['siteName'] == 'General' ? 1 : 0;
		}
		else {
			$parts = explode(':', $this->modelId);
			$this->type = $parts[0];
		}

		$record = parent::mapModelRecordToStorageRecord($onInsert);

		if ($onInsert) {
			// if ($this->type == 1) $record['user_id'] = $this->userId;
			$record['currency'] = 0;
		}

		return $record;
	}

	public function mapModelFieldToStorageField($field, $value) {
		switch ($field) {
			case 'more': return array();
			case 'ratingCount': return array('rating_count' => $value);
			case 'reviews': return array();
			case 'offers': return array();
			case 'image': return array('image_url' => $value);
			case 'productSid':
				if ($this->type == 1) {
					return array('url' => $value);
				}
				else {
					return array('sid' => $value);
				}
			case 'siteName': 
				if ($this->type == 1) {
					return array('type' => 1);
				}
				else {
					return array('site_id' => self::modelSiteNameToStorageSiteId($value), 'type' => 0);
				}
			case 'retrievalId':
				return array('retrieval_id' => $value);
			case 'purchased': return $value ? 1 : 0;
		}

		return $value;
	}

	public function primaryStorageKeysFromModelRecord($modelRecord) {
		if ($modelRecord['site'] == 'General') {
			return array('url' => $modelRecord['productSid']);
		}
		else {
			return array('site_id' => static::modelSiteNameToStorageSiteId($modelRecord['siteName']), 'sid' => $modelRecord['productSid']);
		}
	}

	// storage to model
	public function storageLocationToModelId($storageTable, $storageId) {
		$row = mysqli_fetch_assoc($this->query("SELECT * FROM m_products WHERE id = $storageId"));
		return static::deriveModelIdFromStorageRecord($storageTable, $row);
	}

	public static function deriveModelIdFromStorageRecord($storageTable, $storageRecord) {
		return "$storageRecord[type]:$storageRecord[id]";
	}
	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		if (USE_RIAK) {
			$riak = riakClient();
			$bucket = $riak->bucket("$this->userId.products");
			$object = $bucket->get(/*$modelId*/'test');
			if ($_GET['debug']) {
				var_dump($object->getData());
			}
		}
		$record = mysqli_fetch_assoc($this->query("SELECT * FROM user_products WHERE product_id = $storageRecord[id] && user_id = $this->userId"));

		$modelRecord = array(
			'title' => $record['title'] ? $record['title'] : $storageRecord['title'],
			'price' => $record['price'] ? $record['price'] : $storageRecord['price'],
			'rating' => $record['rating'] ? $record['rating'] : $storageRecord['rating'],
			'status' => $record['status'] ? $record['status'] : $storageRecord['status'],
			'ratingCount' => $record['rating_count'] ? $record['rating_count'] : $storageRecord['rating_count'],
			'image' => $record['image_url'] ? $record['image_url'] : $storageRecord['image_url'],
			'last_scraped_at' => $record['last_scraped_at'],
			'scraper_version' => $record['scraper_version'],
			'purchased' => !!$record['purchased'],
		);
		if ($storageRecord['type'] == 0) {
			$modelRecord += array(
				'siteName' => static::storageSiteIdToModelSiteName($storageRecord['site_id']),
				'productSid' => $storageRecord['sid'],
				'retrievalId' => $record['retrieval_id'],
				'offer' => $record['offer']
			);

			if (USE_RIAK) {
				$modelRecord += array('offers' => $object->getData()['offers']);
				if (defined('GET_RIAK_FIELDS')) {
					$modelRecord += array('more' => $object->getData()['more'], 'reviews' => $object->getData()['reviews']);
				}
			}
		}
		else {
			$modelRecord += array(
				'siteName' => 'General',
				'productSid' => $storageRecord['url']
			);
		}

		return $modelRecord;
	}

	private function riakFields() {
		return array('more', 'offers', 'reviews');
	}

	private function riakData() {
		$data = array('test' => 'test');
		foreach ($this->riakFields() as $field) {
			if (isset($this->modelRecord[$field])) {
				$data[$field] = $this->modelRecord[$field];
			}
		}
		return $data;
	}

	public function executeInsert() {
		// $id = parent::executeInsert();

		global $mysqli;
		$storageRecord = $this->mapModelRecordToStorageRecord(true);

		// if ($this->validate($this->storageTable, $storageRecord)) {
			if (is_array($this->storageId)) {
				$storageRecord = array_merge($storageRecord, $this->storageId);
			}

			$values = array(
				'type' => $storageRecord['type'],
				'site_id' => $storageRecord['site_id'],
				'sid' => $storageRecord['sid'],
				'title' => $storageRecord['title'],
				'image_url' => $storageRecord['image_url'],
				'rating' => $storageRecord['rating'],
				'rating_count' => $storageRecord['rating_count'],
				'price' => $storageRecord['price'],
				'status' => $storageRecord['status'],
			);
			$setQueryPart = static::setQueryPart($values);
			$this->query("INSERT INTO `m_$this->storageTable` SET $setQueryPart");
			$id = mysqli_insert_id($mysqli);


			$setQueryPart = static::setQueryPart($storageRecord);

			$this->query("INSERT IGNORE INTO user_products SET updated_at = UTC_TIMESTAMP(), product_id = $id, user_id = $this->userId, $setQueryPart");

			$onInsert = TableHandler::$onInsert;

			$storageRecord['id'] = $id;
			$modelId = static::deriveModelIdFromStorageRecord($this->storageTable, $storageRecord);
			$onInsert($this->modelTableName(), $this->modelId /* this is a temporary id */, $modelId);
			$id = $modelId;
		// }


		if (USE_RIAK) {
			$riak = riakClient();
			$bucket = $riak->bucket("$this->userId.products");
			$bucket->newObject(/*$id*/'test', $this->riakData())->store();
		}
		return $id;
	}

	public function executeUpdate() {
		// parent::executeUpdate();
		$values = $this->mapModelRecordToStorageRecord();


		$setQueryPart = static::setQueryPart($values);
		if ($setQueryPart) {
			global $mysqli;

			$idQueryPart = $this->idQueryPart($this->storageId);
			if ($values['title']) {
				$sql[] = "title = IFNULL(title, '" . mysqli_real_escape_string($mysqli, $values['title']) . "')";
			}
			if ($values['image_url']) {
				$sql[] = "image_url = IFNULL(image_url, '". mysqli_real_escape_string($mysqli, $values['image_url']) . "')";
			}
			if ($values['rating']) {
				$sql[] = "rating = IFNULL(rating, '". mysqli_real_escape_string($mysqli, $values['rating']) . "')";
			}
			if ($values['rating_count']) {
				$sql[] = "rating_count = IFNULL(rating_count, '". mysqli_real_escape_string($mysqli, $values['rating_count']) . "')";
			}
			if ($values['price']) {
				$sql[] = "price = IFNULL(price, '". mysqli_real_escape_string($mysqli, $values['price']) . "')";
			}
			if ($values['status']) {
				$sql[] = "status = IFNULL(status, '". mysqli_real_escape_string($mysqli, $values['status']) . "')";
			}
			if ($sql) {
				$this->query("UPDATE `m_$this->storageTable` SET " . implode(',', $sql) . " WHERE $idQueryPart");
			}


			// $this->query("UPDATE `user_products` SET $setQueryPart WHERE user_id = $this->userId && product_id = $this->storageId");


			$this->query("UPDATE `user_products` SET $setQueryPart, updated_at = UTC_TIMESTAMP() WHERE user_id = $this->userId && product_id = $this->storageId");
			if (!mysqli_affected_rows($mysqli)) {
				if (!$values['sid'] || !$values['site_id']) {
					$row = mysqli_fetch_assoc($this->query("SELECT sid, site_id FROM m_products WHERE id = $this->storageId"));
					$values['sid'] = $row['sid'];
					$values['site_id'] = $row['site_id'];
					$setQueryPart = static::setQueryPart($values);
				}
				$this->query("INSERT INTO user_products SET product_id = $this->storageId, user_id = $this->userId, updated_at = UTC_TIMESTAMP(), $setQueryPart");
			}
		}

		if (USE_RIAK) {
			$riak = riakClient();
			$bucket = $riak->bucket("$this->userId.products");
			$object = $bucket->get(/*$this->modelId*/'test');
			if ($object->getData()) {
				$object->setData(array_merge($object->getData(), $this->riakData()))->store();
			}
			else {
				$bucket->newObject(/*$this->modelId*/'test', $this->riakData())->store();
			}
		}

		// $bucket->get($this->modelId)->setData(array(
		// 	'more' => $this->modelRecord['more'],
		// 	'offers' => $this->modelRecord['offers'],
		// ))->store();
	}

	public function executeDelete() {
	}
}

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

class UsersTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'users'; }
	public function mapModelFieldToStorageField($field, $value) {
		return $value;
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'name' => $storageRecord['name'],
			'tutorials' => $storageRecord['tutorials'],
			'user_colors' => $storageRecord['user_colors'],
			'tutorial_step' => $storageRecord['tutorial_step'],
		);
	}
}

class ObjectReferencesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'object_references'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	// public function mapModelFieldToStorageField($field, $value) {
	// 	return $value;
	// }

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'object' => $storageRecord['object'],
			'object_user_id' => $storageRecord['object_user_id'],
			'creator_id' => $storageRecord['creator_id'],
		);
	}
}


class SessionsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'sessions'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'collapsed') {
			return $value ? 1 : 0;
		}
		else {
			return $value;
		}
	}
}

class BeltsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'belts'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapModelFieldToStorageField($field, $value) {
		if ($field == 'shared') {
			return $value ? 1 : 0;
		}

		return $value;
	}


	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'title' => $storageRecord['title'],
			'shared' => $storageRecord['shared'],
			'user_id' => 'G' . $storageRecord['user_id'],
			'creator_id' => $storageRecord['creator_id'],
		);
	}

}

class BeltElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'belt_elements'; }
	public static function parentIdField() { return 'belt_id'; }
	public static function parentTable() { return 'belts'; }
}

class RootElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'root_elements'; }
	public static function hasParent() { return false; }
	public function includeUserId() { return true; }
	public function primaryStorageKeysFromModelRecord($modelRecord) { return null; }
}
class CollectionElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'collection_elements'; }
	public static function hasParent() { return false; }
}

class SessionElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'session_elements'; }
	public static function parentIdField() { return 'session_id'; }
	public static function parentTable() { return 'sessions'; }
}

class BundlesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'bundles'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }
}

class BundleElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'bundle_elements'; }
	public static function parentIdField() { return 'bundle_id'; }
	public static function parentTable() { return 'bundles'; }
}

class CompositesTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'composites'; }
	public function storageTableHasUserIdField() { return true; }
}
class CompositeElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'composite_elements'; }
	public static function parentIdField() { return 'composite_id'; }
	public static function parentTable() { return 'composites'; }
}

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

class ListsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'lists'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'collapsed') {
			return $value ? 1 : 0;
		}
		else {
			return $value;
		}
	}
}
class ListElementsTableHandler extends ElementsTableHandler {
	public static function modelTableName() { return 'list_elements'; }
	public static function parentIdField() { return 'list_id'; }
	public static function parentTable() { return 'lists'; }
}

// class CompetitiveListsTableHandler extends SqlTableHandler {
// 	public static function modelTableName() { return 'competitive_lists'; }
// 	public function storageTableHasUserIdField() { return true; }
// }
// class CompetitiveListElementsTableHandler extends ElementsTableHandler {
// 	public static function modelTableName() { return 'competitive_list_elements'; }
// 	public static function parentIdField() { return 'competitive_list_id'; }
// 	public static function parentTable() { return 'competitive_lists'; }
// }

class DecisionsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'decisions'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }

	public function mapModelFieldToStorageField($field, $value) {
		/*if ($field == 'element_id') {
			return $this->db->resolveIdToStorageId(map($this->modelRecord), $value);
		}
		else */if ($field == 'list_id') {
			return $this->db->resolveIdToStorageId('lists', $value);
		}
		else if ($field == 'shared') {
			if ($this->userId == $this->clientUserId) {
				return $value ? 1 : 0;				
			}
			else {
				return array();
			}
		}

		// else if ($field == 'dismissal_list_id') {
		// 	return $this->db->resolveIdToStorageId('lists', $value);
		// }

		return $value;
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		// $elementTable = modelNameToTableName($storageRecord['element_type']);

		return array(
			'list_id' => $this->db->tableHandler('lists')->storageLocationToModelId('lists', $storageRecord['list_id']),
			// 'dismissal_list_id' => $this->db->tableHandler('lists')->storageLocationToModelId('lists', $storageRecord['dismissal_list_id']),
			'display_options' => $storageRecord['display_options'],
			'share_title' => $storageRecord['share_title'],
			'share_message' => $storageRecord['share_message'],
			'shared' => $storageRecord['shared'],
			'creator_id' => $storageRecord['creator_id'],
			'access' => $storageRecord['access'],
			// 'element_type' => $storageRecord['element_type'],
			// 'element_id' => $this->db->tableHandler($elementTable)->storageLocationToModelId($elementTable, $storageRecord['element_id']),
		);
	}
}

class DecisionElementsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'decision_elements'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return false; }

	public function mapModelFieldToStorageField($field, $value) {
		if ($field == 'decision_id') {
			return $this->db->resolveIdToStorageId('decisions', $value);
		}
		else if ($field == 'list_element_id') {
			return $this->db->resolveIdToStorageId('list_elements', $value);
		}
		else if ($field == 'selected') {
			return $value ? 1 : 0;
		}
		else if ($field == 'dismissed') {
			return $value ? 1 : 0;
		}

		return $value;
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'decision_id' => $this->db->tableHandler('decisions')->storageLocationToModelId('decisions', $storageRecord['decision_id']),
			'list_element_id' => $this->db->tableHandler('list_elements')->storageLocationToModelId('list_elements', $storageRecord['list_element_id']),
			'selected' => $storageRecord['selected'],
			'dismissed' => $storageRecord['dismissed'],
			'row' => $storageRecord['row'],
			'creator_id' => $storageRecord['creator_id'],
		);
	}
}


class SiteSettingsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'site_settings'; }
	public function storageTableHasUserIdField() { return true; }

	public function primaryStorageKeysFromModelRecord($modelRecord) {
		return array('user_id' => $this->userId, 'site' => $modelRecord['site']);
	}
}

class DataTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'data'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }



	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'element_id' && $value) {
			return $this->db->resolveIdToStorageId(map($this->modelRecord), $value);
		}

		return $value;
	} 

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'url' => $storageRecord['url'],
			'text' => $storageRecord['text'],
			'type' => $storageRecord['type'],
			'comment' => $storageRecord['comment'],
			'title' => $storageRecord['title'],
			'creator_id' => $storageRecord['creator_id'],
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
}

class FeelingsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'feelings'; }
	public function storageTableHasUserIdField() { return true; }


	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'element_id' && $value) {
			return $this->db->resolveIdToStorageId(map($this->modelRecord), $value);
		}

		return $value;
	} 

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'thought' => $storageRecord['thought'],
			'negative' => $storageRecord['negative'],
			'positive' => $storageRecord['positive'],
			'timestamp' => $storageRecord['timestamp'],
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
}

class ArgumentsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'arguments'; }
	public function storageTableHasUserIdField() { return true; }


	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'element_id' && $value) {
			return $this->db->resolveIdToStorageId(map($this->modelRecord), $value);
		}

		return $value;
	} 

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'thought' => $storageRecord['thought'],
			'for' => $storageRecord['for'],
			'against' => $storageRecord['against'],
			'timestamp' => $storageRecord['timestamp'],
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
}

class DescriptorsTableHandler extends SqlTableHandler {
	public static function modelTableName() { return 'descriptors'; }
	public function storageTableHasUserIdField() { return true; }
	public function storageTableHasCreatorIdField() { return true; }


	protected function mapModelFieldToStorageField($field, $value) {
		if ($field == 'element_id' && $value) {
			return $this->db->resolveIdToStorageId(map($this->modelRecord), $value);
		}

		return $value;
	} 

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		$modelRecord = array(
			'descriptor' => $storageRecord['descriptor'],
			'creator_id' => $storageRecord['creator_id'],
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
}


class Storage extends DBStorage {
	public function getData($args) {
		$modelRecords = array();
		$elementsQueue = (array)$args['elements'];
		$recordsQuery = (array)$args['records'];

		$productIds = array();

		if ($args['root']) {
			$table = modelNameToTableName($args['root']);
			$query = "SELECT * FROM m_$table WHERE user_id = $this->userId";
			$result = $this->query($query);
			$tableHandler = $this->tableHandler($table);

			while ($row = mysqli_fetch_assoc($result)) {
				$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
				$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
				if ($table == 'belts') {
					$elementsQueue[] = array('belt_elements', 'belt_id', $row['id']);
				}
			}
		}

		// var_dump($elementsQueue);
		// var_dump($modelRecords);

		do {
			foreach ($elementsQueue as $top) {
				if ($top == 'Root') {
					$table = 'root_elements';
					$query = "SELECT * FROM m_root_elements WHERE user_id = $this->userId";
				}
				// else if ($top == 'Collections') {
				// 	$table = 'collection_elements';
				// 	$query = "SELECT * FROM m_collection_elements WHERE user_id = $this->userId";
				// }
				else {
					list($table, $field, $id) = $top;
					$query = "SELECT * FROM m_$table WHERE $field = $id";
				}

				$result = $this->query($query);
				$tableHandler = $this->tableHandler($table);	
				while ($row = mysqli_fetch_assoc($result)) {
					$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
					$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
					if ($row['element_type']) {
						if ($row['element_type'] == 'Product') {
							$productIds[] = $row['element_id'];
						}
						else {
							$recordsQuery[$row['element_type']][] = $row['element_id'];
						}
					}

					if ($table == 'list_elements') {
						$listElementIds[] = $row['id'];
					}
				}
			}

			unset($elementsQueue);

			$allRecordsQuery = array();

			do {
				$newRecordsQuery = array();
				foreach ($recordsQuery as $modelName => $ids) {
					$allRecordsQuery[$modelName] = array_merge((array)$allRecordsQuery[$modelName], $ids);

					$table = modelNameToTableName($modelName);

					$query = "SELECT * FROM m_$table WHERE id IN (" . implode(', ', $ids) . ')';
					$result = $this->query($query);
					$tableHandler = $this->tableHandler($table);

					while ($row = mysqli_fetch_assoc($result)) {
						$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
						$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
						switch ($table) {
							case 'product_variants':
								$productIds[] = $row['product_id'];
								break;

							case 'decisions':
								$newRecordsQuery['List'][] = $row['list_id'];
								// $elementsQueue[] = array('decision_elements', 'decision_id', $row['id']);
								break;

							case 'descriptors':
								if ($row['element_id']) {
									$newRecordsQuery[$row['element_type']][] = $row['element_id'];
								}
								break;

							case 'competitive_lists':
								$elementsQueue[] = array('competitive_list_elements', 'competitive_list_id', $row['id']);
								break;

							case 'lists':
								$elementsQueue[] = array('list_elements', 'list_id', $row['id']);
								break;

							case 'belts':
								$elementsQueue[] = array('belt_elements', 'belt_id', $row['id']);
								break;

							case 'bundles':
								$elementsQueue[] = array('bundle_elements', 'bundle_id', $row['id']);
								break;

							case 'sessions':
								$elementsQueue[] = array('session_elements', 'session_id', $row['id']);
								break;

							case 'composites':
								$elementsQueue[] = array('composite_elements', 'composite_id', $row['id']);
								$elementsQueue[] = array('composite_slots', 'composite_id', $row['id']);
								break;
						}
					}
					// var_dump($newRecordsQuery);
				}
				$recordsQuery = $newRecordsQuery;
			} while ($recordsQuery);
		} while ($elementsQueue);

		// var_dump($this->userId);
		if ($args['products']) {
			$tableHandler = $this->tableHandler('products');

			if ($args['products'] == 'referenced') {
				if ($productIds) {
					$result = $this->query("SELECT * FROM m_products WHERE id IN (" . implode(', ', $productIds) . ')');
					while ($row = mysqli_fetch_assoc($result)) {
						$allRecordsQuery['Product'][] = $row['id'];
						$modelId = $tableHandler->deriveModelIdFromStorageRecord('products', $row);
						$modelRecords['products'][$modelId] = $tableHandler->mapStorageRecordToModelRecord('products', $row, $modelId);
					}
				}
			} 
			else {
				$result = $this->query("SELECT * FROM user_products WHERE user_id = $this->userId");
				while ($row = mysqli_fetch_assoc($result)) {
					$row['id'] = $row['product_id'];
					$allRecordsQuery['Product'][] = $row['id'];
					$modelId = $tableHandler->deriveModelIdFromStorageRecord('products', $row);
					$modelRecords['products'][$modelId] = $tableHandler->mapStorageRecordToModelRecord('products', $row, $modelId);
				}
			}
			
			// $result = $this->query("SELECT * FROM m_product_variants WHERE user_id = $this->userId");
			// $tableHandler = $this->tableHandler('product_variants');
			// while ($row = mysqli_fetch_assoc($result)) {
			// 	$allRecordsQuery['ProductVariant'][] = $row['id'];
			// 	$modelId = $tableHandler->deriveModelIdFromStorageRecord('product_variants', $row);
			// 	$modelRecords['product_variants'][$modelId] = $tableHandler->mapStorageRecordToModelRecord('product_variants', $row, $modelId);
			// }
		}

		if ($listElementIds) {
			$table = 'decision_elements';
			$query = "SELECT * FROM m_$table WHERE list_element_id IN (" . implode(', ', $listElementIds) . ')';
			$result = $this->query($query);
			$tableHandler = $this->tableHandler($table);
			while ($row = mysqli_fetch_assoc($result)) {
				$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
				$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
			}
		}

		foreach ($allRecordsQuery as $table => &$ids) {
			sort($ids);
		}
		unset($ids);

		if ($args['auxiliary']) {
			foreach (array('data', 'feelings', 'arguments') as $table) {
				$query = [];

				foreach ($allRecordsQuery as $modelName => $ids) {
					foreach ($ids as $id) {
						$query[] = "element_type = '$modelName' && element_id = '$id' && user_id = $this->userId";
					}
				}

				// var_dump($query);

				if ($query) {
					$query = '(' . implode(') || (', $query) . ')';

					$result = $this->query("SELECT * FROM m_$table WHERE $query");
					$tableHandler = $this->tableHandler($table);
					while ($row = mysqli_fetch_assoc($result)) {
						$modelId = $tableHandler->deriveModelIdFromStorageRecord($table, $row);
						$modelRecords[$table][$modelId] = $tableHandler->mapStorageRecordToModelRecord($table, $row, $modelId);
					}
				}
			}
		}

		// var_dump($modelRecords);


		// $result = $this->query("SELECT * FROM m_feelings WHERE user_id = $this->userId");
		// $tableHandler = $this->tableHandler('feelings');
		// while ($row = mysqli_fetch_assoc($result)) {
		// 	$modelRecords['feelings'][$tableHandler->deriveModelIdFromStorageRecord('feelings', $row)] = $tableHandler->mapStorageRecordToModelRecord('feelings', $row);
		// }

		return $modelRecords;
	}

	public function getAllForUser() {
		return $this->getData(array(
			'root' => 'Belt',
			// 'elements' => 'Root',
			'records' => array('User' => array($this->userId)),
			'products' => true,
			'auxiliary' => true
		));
	}
}


function makeDb($userId, $clientUserId) {
	return new DB($userId, $clientUserId, new Storage($userId, $clientUserId, array(
		'users' => UsersTableHandler,
		'products' => ProductsTableHandler,
		'product_variants' => ProductVariantsTableHandler,
		'decisions' => DecisionsTableHandler,
		'decision_elements' => DecisionElementsTableHandler,
		'sessions' => SessionsTableHandler,
		'session_elements' => SessionElementsTableHandler,
		'root_elements' => RootElementsTableHandler,
		'belts' => BeltsTableHandler,
		'belt_elements' => BeltElementsTableHandler,
		'bundles' => BundlesTableHandler,
		'bundle_elements' => BundleElementsTableHandler,
		'composites' => CompositesTableHandler,
		'composite_slots' => CompositeSlotsTableHandler,
		'lists' => ListsTableHandler,
		'list_elements' => ListElementsTableHandler,
		// 'competitive_lists' => CompetitiveListsTableHandler,
		// 'competitive_list_elements' => CompetitiveListElementsTableHandler,
		'composite_elements' => CompositeElementsTableHandler,
		'site_settings' => SiteSettingsTableHandler,
		'data' => DataTableHandler,
		'feelings' => FeelingsTableHandler,
		'arguments' => ArgumentsTableHandler,
		'descriptors' => DescriptorsTableHandler,
		'object_references' => ObjectReferencesTableHandler,
	)));
}
