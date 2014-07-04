<?php

require_once('includes/header.php');

header('Content-Type: text/plain');

// $result = mysqli_query($mysqli, "SELECT id FROM m_users");
// while ($row = mysqli_fetch_assoc($result)) {
// 	mysqli_query($mysqli, "INSERT INTO m_belts SET user_id = $row[id], creator_id = $row[id]");
// 	$beltId = mysqli_insert_id($mysqli);

// 	$rootElementsResult = mysqli_query($mysqli, "SELECT * FROM m_root_elements WHERE user_id = $row[id]");
// 	while ($rootElement = mysqli_fetch_assoc($rootElementsResult)) {
// 		mysqli_query($mysqli, "INSERT INTO m_belt_elements SET 
// 			user_id = $rootElement[user_id],
// 			belt_id = $beltId,
// 			creator_id = $rootElement[creator_id],
// 			element_type = '$rootElement[element_type]',
// 			element_id = $rootElement[element_id],
// 			`index` = $rootElement[index],
// 			`created_at` = '$rootElement[created_at]'");
// 	}
// }

$tables = array(
	'm_list_elements',
	'm_belt_elements',
	'm_session_elements',
	'm_bundle_elements',
	'm_feelings',
	'm_data',
);

$result = mysqli_query($mysqli, 'SELECT * FROM m_products_old');
while ($row = mysqli_fetch_assoc($result)) {
	$products[$row['site_id']][$row['sid']][] = $row;
}

function value($value) {
	if ($value == null) {
		return 'NULL';
	}
	else {
		global $mysqli;
		return '"' . mysqli_real_escape_string($mysqli, $value) . '"';
	}
}

foreach ($products as $siteId => $siteProducts) {
	foreach ($siteProducts as $sid => $rows) {
		$row = $rows[0];
		mysqli_query($mysqli, "INSERT INTO m_products " .
			'type = ' . value($row['type']) . ',' .
			'sid = ' . value($row['sid']) . ',' .
			'site_id = ' . value($row['site_id']) . ',' .
			'title = ' . value($row['title']) . ',' .
			'image_url = ' . value($row['image_url']) . ',' .
			'price = ' . value($row['price']) . ',' .
			'retrieval_id = ' . value($row['retrieval_id']) . ',' .
			'currency = ' . value($row['currency']) . ',' .
			'url = ' . value($row['url']) . ',' .
			'offer = ' . value($row['offer']) . ',' .
			'rating = ' . value($row['rating']) . ',' .
			'rating_count = ' . value($row['rating_count']) . ',' .
			'created_at = ' . value($row['created_at']) . ',' .
			'last_scraped_at = ' . value($row['last_scraped_at']) . ',' .
			'status = ' . value($row['status'])) or die(mysqli_error($mysqli));

		$id = mysqli_insert_id($msyqli);
		$newId[$siteId][$sid] = $id;
	}
}

var_dump($newId);


var_dump($products);