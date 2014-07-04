<?php

require_once('includes/header.php');
echo 'test';
var_dump(ENV);
$result = mysqli_query($mysqli, "SELECT id FROM m_users");
while ($row = mysqli_fetch_assoc($result)) {
	var_dump($row);
	mysqli_query($mysqli, "INSERT INTO m_belts SET user_id = $row[id], creator_id = $row[id]");
	$beltId = mysqli_insert_id($mysqli);

	$rootElementsResult = mysqli_query($mysqli, "SELECT * FROM m_root_elements WHERE user_id = $row[id]");
	while ($rootElement = mysqli_fetch_assoc($rootElementsResult)) {
		mysqli_query($mysqli, "INSERT INTO m_belt_elements SET 
			user_id = $rootElement[user_id],
			belt_id = $beltId,
			creator_id = $rootElement[creator_id],
			element_type = '$rootElement[element_type]',
			element_id = $rootElement[element_id],
			`index` = $rootElement[index],
			`created_at` = '$rootElement[created_at]'");
	}
}
