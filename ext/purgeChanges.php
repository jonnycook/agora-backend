<?php

require_once('includes/header.php');


$before = mongoDb()->changes->count();
mongoDb()->changes->remove(array(
	'lastChecked' => array(
		'$lt' => time() - 60
	)
));


$after = mongoDb()->changes->count();

echo $before - $after, ' purged, ', $after, ' left';

$date = gmdate('Y-m-d H:i:s', time() - 60);

mysqli_query($mysqli, "DELETE FROM clients WHERE last_seen_at < '$date'");
echo '<br>', mysqli_affected_rows($mysqli);