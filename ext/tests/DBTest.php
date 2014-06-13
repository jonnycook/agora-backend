<?php

define('ENV', 'test');

ini_set('xdebug.var_display_max_depth', -1);

require_once('includes/header.php');
require_once('includes/DB.php');

class DB_Test extends DB {
	// public function isFk($table, $field) {
	// 	return parent::isFk($table, $field);
	// }
}

class DBTestHelper {
	function convertId($table, $id) {
		if ($id[0] == 'G') return $id;
		return 'G' . ($this->localToGlobalMap[$table][$id] ? $this->localToGlobalMap[$table][$id] : $id);
	}

	function convertFk($table, $field, $value) {
		if ($value[0] == 'G') return $value;
		$referentTable = DB_Test::$models[$table]['referents'][$field];
		return 'G' . ($this->localToGlobalMap[$referentTable][$value] ? $this->localToGlobalMap[$referentTable][$value] : $value);
	}

	function generateExpectedChanges() {
		foreach ($this->input as $table => $records) {
			foreach ($records as $id => $record) {
				foreach ($record as $field => $value) {
					$globalId = $this->convertId($table, $id);
					
					if (DB_Test::isFk($table, $field)) {
						$expectedChanges[$table][$globalId][$field] = $this->convertFk($table, $field, $value);
					}
					else {
						$expectedChanges[$table][$globalId][$field] = $value;
					}
				}
			}
		}
		return $expectedChanges;
	}
}

class DBTest extends PHPUnit_Framework_TestCase {

	private function truncateTable($table) {
		$this->query("TRUNCATE TABLE $table");
	}

	private function truncateTables($tables = 'all') {
		if ($tables == 'all') {
			$result = mysql_query("SHOW TABLES FROM bagit_aws_test");
			while ($row = mysql_fetch_row($result)) {
				$this->truncateTable($row[0]);
			}
		}
		else {
			foreach ($tables as $table) {
				$this->truncateTable($table);
			}
		}
	}

	private function fixtures($fixtures, $truncate = 'fixtureTables') {
		$this->truncateTables($truncate == 'fixtureTables' ? array_keys($fixtures) : $truncate);
		$this->recordIds = null;
		foreach ($fixtures as $table => $records) {
			foreach ($records as $id => $record) {
				mysql_query("INSERT INTO $table SET " . 
					implode(',', array_map(function($key) use ($record) {
						return "`$key` = '{$record[$key]}'";
					}, array_keys($record))));

				$this->recordIds->$table->$id = mysql_insert_id();
			}
		}
	}

	private function query($sql) {
		$result = mysql_query($sql);
		if (!$result) die(mysql_error() . ": $sql");
		return $result;
	}

	private function assertArrayContainsArray($array, $subArray) {
		foreach ($subArray as $key => $value) {
			$this->assertEquals($value, $array[$key], "For key '$key':");
		}
	}

	private function assertArrayContainsArrayR($array, $subArray) {
		foreach ($subArray as $key => $value) {
			$this->assertArrayHasKey($key, $array);
			if (is_array($value)) {
				$this->assertArrayContainsArrayR($array[$key], $value);
			}
			else {
				$this->assertEquals($value, $array[$key], "For key $key:");
			}
		}
	}


	private function assertRecord($table, $id, $values = array()) {
		if (is_array($id)) {
			$idSql = implode(' && ', array_map(function($key) use ($id) { return "`$key` = '$id[$key]'"; }, array_keys($id)));
			$id = json_encode($id);
		}
		else {
			$idSql = "id = '$id'";
		}

		$record = mysqli_fetch_assoc($this->query("SELECT * FROM `$table` WHERE $idSql"));

		$this->assertNotEquals($record, false, "record $table/$id doesn't exist");

		$this->assertArrayContainsArray($record, $values);
	}

	private function assertRecordAbsent($table, $id) {
		if (is_array($id)) {
			$idSql = implode(' && ', array_map(function($key) use ($id) { return "`$key` = '$id[$key]'"; }, array_keys($id)));
		}
		else {
			$idSql = "id = '$id'";
		}

		$record = mysqli_fetch_assoc($this->query("SELECT * FROM `$table` WHERE $idSql"));

		$this->assertFalse($record, "record $table/$id isn't absent");

	}

	public function testIt() {
		$this->fixtures(array(
			'users' => array(
				'user' => array('email' => '')
			)
		), 'all');

		$userId = $this->recordIds->users->user;

		$db = new DB($userId, new DBStorage($userId));

		$db->execute($input = array(
			'bags' => array(
				1 => array(
					'name' => 'Bag',
					'index' => 1,
					'type' => 'all'
				)
			),

			'products' => array(
				1 => array(
					'siteName' => 'General',
					'productSid' => 'asparagus',
					'title' => 'NAME',
					'image' => 'IMAGE',
				),
				2 => array(
					'siteName' => 'Amazon',
					'productSid' => 'SID',
					'price' => 2.12,
					'title' => 'Aspergers',
					'image' => 'off',
					'currency' => 1,
				),
				3 => array(
					'siteName' => 'Amazon',
					'productSid' => 'SID1',
					'price' => '',
					'title' => '',
					'image' => '',
					'currency' => '',
				),
				4 => array(
					'siteName' => 'Amazon',
					'productSid' => 'SID2',
					'price' => null,
					'title' => null,
					'image' => null,
					'currency' => null,
				),
			),

			'products_bags' => array(
				1 => array(
					'bag_id' => 1,
					'product_id' => 1,
					'index' => 1
				),

				2 => array(
					'bag_id' => 1,
					'product_id' => 2,
					'index' => 2
				)
			),

			'site_settings' => array(
				1 => array(
					'site' => 'Amazon',
					'enabled' => 1
				)
			)
		));


		$helper = new DBTestHelper;
		$helper->input = $input;
		$helper->localToGlobalMap = array(
			'products' => array(
				1 => 'u1',
				2 => '1'
			),
			'products_bags' => array(
				1 => 'u1:1',
				2 => '1:1',
			),
		);

		$mapping = $db->mapping();

		$this->assertRecord('m_bags', 1, array('name' => 'Bag', 'user_id' => $userId, 'index' => 1, 'type' => 1));
		$this->assertArrayContainsArrayR($mapping, array('bags' => array(1 => 'G1')));
		$this->assertRecord('changes', array('table' => 'bags', 'rid' => 1, 'user_id' => $userId));

		$this->assertRecord('m_user_products', 1, array('url' => 'asparagus', 'user_id' => $userId, 'title' => 'NAME', 'image_url' => 'IMAGE'));
		$this->assertArrayContainsArrayR($mapping, array('products' => array(1 => 'Gu1')));
		$this->assertRecord('changes', array('table' => 'products', 'rid' => 'u1', 'user_id' => $userId));

		$this->assertRecord('m_products', 1, array('sid' => 'SID', 'site_id' => 1, /*'price' => 2.12, 'title' => 'Aspergers', 'image_url' => 'off', 'currency' => 1*/));
		$this->assertArrayContainsArrayR($mapping, array('products' => array(2 => 'G1')));
		$this->assertRecord('changes', array('table' => 'products', 'rid' => 1, 'user_id' => $userId));

		$this->assertRecord('m_user_products_bags', array('bag_id' => 1, 'user_product_id' => 1), array('index' => 1, 'user_id' => $userId));
		$this->assertArrayContainsArrayR($mapping, array('products_bags' => array(1 => 'Gu1:1')));
		$this->assertRecord('changes', array('table' => 'products_bags', 'rid' => 'u1:1', 'user_id' => $userId));

		$this->assertRecord('m_products_bags', array('bag_id' => 1, 'product_id' => 1), array('index' => 2, 'user_id' => $userId));
		$this->assertArrayContainsArrayR($mapping, array('products_bags' => array(2 => 'G1:1')));
		$this->assertRecord('changes', array('table' => 'products_bags', 'rid' => '1:1', 'user_id' => $userId));

		$this->assertRecord('m_site_settings', 1, array('enabled' => 1, 'user_id' => $userId));
		$this->assertArrayContainsArrayR($mapping, array('site_settings' => array(1 => 'G1')));
		$this->assertRecord('changes', array('table' => 'site_settings', 'rid' => '1', 'user_id' => $userId));


		$db = new DB_Test($userId, new DBStorage($userId));

		list($changes, $lastTime) = $db->changes(null, true);
		$db->execute($input = array(
			'products_bags' => array(
				1 => array(
					'bag_id' => 'G1',
					'product_id' => 'Gu1',
					'index' => 123
				),
				2 => array(
					'bag_id' => 'G1',
					'product_id' => 'G1',
					'index' => 123
				),
			)
		));


		$db = new DB_Test($userId, new DBStorage($userId));
		list($changes, $lastTime) = $db->changes(null, true);

		$helper->input = $input;

		$this->assertNotNull($lastTime);
		$this->assertArrayContainsArrayR($changes, $helper->generateExpectedChanges());


		$db = new DB_Test($userId, new DBStorage($userId));
		list($changes, $lastTime) = $db->changes(null);

		$this->assertNotNull($lastTime);
		$this->assertArrayContainsArrayR($changes, $helper->generateExpectedChanges());


		$db = new DB_Test($userId, new DBStorage($userId));
		$db->execute($input = array(
			'bags' => array(
				'G1' => array(
					'name' => 'Bag2',
					'type' => 'normal'
				)
			),
			'products' => array(
				'Gu1' => array(
					'productSid' => 'face'
				),
				'G1' => array(
					'productSid' => 'ocean',
					'title' => 'Asparagus',
					'price' => '',
					'image' => null,
				)
			),
			'products_bags' => array(
				'Gu1:1' => array(
					'index' => 10
				),
				'G1:1' => array(
					'index' => 20
				)
			),
			'site_settings' => array(
				'G1' => array(
					'enabled' => 0
				)
			)
		));

		$this->assertRecord('m_bags', 1, array('name' => 'Bag2', 'type' => 2));
		$this->assertRecord('m_user_products', 1, array('url' => 'face'));
		$this->assertRecord('m_products', 1, array('sid' => 'ocean'/*, 'title' => 'Asparagus'*/));
		$this->assertRecord('m_user_products_bags', array('bag_id' => 1, 'user_product_id' => 1), array('index' => 10));
		$this->assertRecord('m_products_bags', array('bag_id' => 1, 'product_id' => 1), array('index' => 20));
		$this->assertRecord('m_site_settings', 1, array('enabled' => 0));

		list($changes, $lastTime) = $db->changes(null, true);

		$helper->input = $input;

		$this->assertNotNull($lastTime);
		$this->assertArrayContainsArrayR($changes, $helper->generateExpectedChanges());

		$db = new DB_Test($userId + 1, new DBStorage($userId + 1));
		$db->execute(array(
			'bags' => array(
				'G1' => 'deleted'
			)
		));
		$this->assertRecord('m_bags', 1);

		$db = new DB_Test($userId + 1, new DBStorage($userId + 1));
		$db->execute(array(
			'bags' => array(
				'G1' => array(
					'name' => 'Basket'
				)
			)
		));
		$this->assertRecord('m_bags', 1, array('name' => 'Bag2'));

		$db = new DB_Test($userId, new DBStorage($userId));
		$db->execute($input = array(
			'bags' => array(
				'G1' => 'deleted'
			),
			'products' => array(
				'Gu1' => 'deleted',
				'G1' => 'deleted'
			),
			'products_bags' => array(
				'Gu1:1' => 'deleted',
				'G1:1' => 'deleted'
			),
			'site_settings' => array(
				'G1' => 'deleted'
			)
		));

		$this->assertRecordAbsent('m_bags', 1);
		$this->assertRecord('changes', array('table' => 'bags', 'rid' => 1, 'user_id' => $userId), array('deleted' => 1));

		$this->assertRecordAbsent('m_user_products', 1);
		$this->assertRecord('changes', array('table' => 'products', 'rid' => 'u1', 'user_id' => $userId), array('deleted' => 1));

		$this->assertRecordAbsent('m_products', 1);
		$this->assertRecord('changes', array('table' => 'products', 'rid' => 1, 'user_id' => $userId), array('deleted' => 1));

		$this->assertRecordAbsent('m_user_products_bags', array('bag_id' => 1, 'user_product_id' => 1));
		$this->assertRecord('changes', array('table' => 'products_bags', 'rid' => 'u1:1', 'user_id' => $userId), array('deleted' => 1));

		$this->assertRecordAbsent('m_products_bags', array('bag_id' => 1, 'product_id' => 1));
		$this->assertRecord('changes', array('table' => 'products_bags', 'rid' => '1:1', 'user_id' => $userId), array('deleted' => 1));

		$this->assertRecordAbsent('m_site_settings', 1);
		$this->assertRecord('changes', array('table' => 'site_settings', 'rid' => 1, 'user_id' => $userId), array('deleted' => 1));

		$db = new DB_Test($userId, new DBStorage($userId));
		list($changes, $time, $fullResultSet) = $db->changes($lastTime);
		$this->assertFalse($fullResultSet);

		DB::clearChanges();
		$db = new DB_Test($userId, new DBStorage($userId));
		list($changes, $time, $fullResultSet) = $db->changes($lastTime);
		$this->assertTrue($fullResultSet);
	}
}
