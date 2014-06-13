<?php

require_once('../includes/DBStorage.php');

class TestDBStorage extends DBStorage {
	protected function query($sql) {
		echo "$sql\n";
	}
}

class DBStorageTest extends PHPUnit_Framework_TestCase {
	public function __construct() {
		$this->storage = new TestDBStorage(1);
		$this->storage->changes = array(
			'products' => array(
				'T1' => array(
					'siteName' => 'General'
				),
				'T2' => array(
					'siteName' => 'asdf'
				),
			),

			'products_bags' => array(
				'T1' => array(
					'bag_id' => 1,
					'product_id' => 'T1'
				),
				'T2' => array(
					'bag_id' => 1,
					'product_id' => 'T2'
				)

			)
		);

	}

	public function testTableFromIdAndTableFromRecord() {
		$storage = $this->storage;
		$this->assertEquals(
			$storage->tableFromRecord('products', array('siteName' => 'General')),
			'user_products'
		);

		$this->assertEquals(
			$storage->tableFromRecord('products', array('siteName' => 'asf')),
			'products'
		);


		$this->assertEquals(
			$storage->tableFromRecord('products_bags', array('product_id' => 1, 'bag_id' => 1)),
			'products_bags'
		);

		$this->assertEquals(
			$storage->tableFromRecord('products_bags', array('product_id' => 'u1', 'bag_id' => 1)),
			'user_products_bags'
		);


		$this->assertEquals(
			$storage->tableFromId('products', 'T1'),
			array('user_products')
		);

		$this->assertEquals(
			$storage->tableFromId('products', 'T2'),
			array('products')
		);

		$this->assertEquals(
			$storage->tableFromId('products', 1),
			array('products', 1)
		);

		$this->assertEquals(
			$storage->tableFromId('products', 'u1'),
			array('user_products', 1)
		);

		$this->assertEquals(
			$storage->tableFromId('products_bags', 'T1'),
			array('user_products_bags')
		);

		$this->assertEquals(
			$storage->tableFromId('products_bags', 'T2'),
			array('products_bags')
		);

		$this->assertEquals(
			$storage->tableFromId('products_bags', '1:2'),
			array('products_bags', array('product_id' => 1, 'bag_id' => 2))
		);

		$this->assertEquals(
			$storage->tableFromId('products_bags', 'u1:2'),
			array('user_products_bags', array('user_product_id' => 1, 'bag_id' => 2))
		);
	}

	public function testNextId() {
		$this->assertEquals($this->storage->nextId('boob'), 'T1');
		$this->assertEquals($this->storage->nextId('boob'), 'T2');
	}

	public function testSave() {
		return;
		$this->storage->changes = array_merge(/*$this->storage->changes*/array(), array(
			'products' => array(
				1 => 'delete'
			),
			'products_bags' => array(
				'1:1' => 'delete'
			),
			'products_bags' => array(
				'u1:1' => 'delete'
			),
		));
		$this->storage->save();
	}
}