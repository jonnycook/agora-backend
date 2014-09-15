<?php

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
		return "0:$storageId";
		// $row = mysqli_fetch_assoc($this->query("SELECT * FROM m_products WHERE id = $storageId"));
		// return static::deriveModelIdFromStorageRecord($storageTable, $row);
	}

	public static function deriveModelIdFromStorageRecord($storageTable, $storageRecord) {
		return "$storageRecord[type]:$storageRecord[id]";
	}
	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		if (USE_RIAK) {
			$riak = riakClient();
			$bucket = $riak->bucket("$this->userId.products");
			$object = $bucket->get($modelId);
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
			$bucket->newObject($id, $this->riakData())->store();
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
			$object = $bucket->get($this->modelId);
			if ($object->getData()) {
				$object->setData(array_merge($object->getData(), $this->riakData()))->store();
			}
			else {
				$bucket->newObject($this->modelId, $this->riakData())->store();
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


return array(
	'class' => ProductsTableHandler,
	'modelName' => 'Product',
	'model' => array(
		'distinct' => array('siteName', 'productSid')
	)
);