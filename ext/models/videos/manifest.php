<?php

class VideosTableHandler extends SqlTableHandler {
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

	public static function modelTableName() { return 'videos'; }
	public function storageTableHasUserIdField() { return false; }
	public function mapModelFieldToStorageField($field, $value) {
		switch ($field) {
			case 'image': return array('image_url' => $value);
			case 'objectId': return array('object_id' => $value);
			case 'siteName': return array('site_id' => self::modelSiteNameToStorageSiteId($value));
		}

		return $value;
	}

	public function primaryStorageKeysFromModelRecord($modelRecord) {
		return array('site_id' => static::modelSiteNameToStorageSiteId($modelRecord['siteName']), 'object_id' => $modelRecord['objectId']);
	}

	public function mapStorageRecordToModelRecord($storageTable, $storageRecord, $modelId) {
		return array(
			'objectId' => $storageRecord['object_id'],
			'siteName' =>  static::storageSiteIdToModelSiteName($storageRecord['site_id']),
			'title' => $storageRecord['title'],
			'duration' => $storageRecord['duration'],
			'image' => $storageRecord['image_url'],
		);
	}
}


return array(
	'class' => VideosTableHandler,
	'modelName' => 'Video',
	'model' => array(
		'distinct' => array('siteName', 'objectId')
	)
);