<?php

require_once('includes/header.php');

$version = mysqli_real_escape_string($mysqli, $_GET['version']);

if ($_GET['time']) {
	$time = mysqli_real_escape_string($mysqli, $_GET['time']);
	$result = mysqli_query($mysqli, "SELECT * FROM (SELECT * FROM scrapers ORDER BY `timestamp` DESC) t WHERE `timestamp` > '$time' && (`version` IS NULL || `version` <= '$version') GROUP BY site, name") or die(mysqli_error($mysqli));
}
else {
	$result = mysqli_query($mysqli, "SELECT * FROM (SELECT * FROM scrapers ORDER BY `timestamp` DESC) t WHERE (`version` IS NULL || `version` <= '$version') GROUP BY site, name") or die(mysqli_error($mysqli));
}

$scrapers = array();
$mostRecentTime = $_GET['time'];
while ($row = mysqli_fetch_assoc($result)) {
	$scraper = json_decode($row['obj']);
	if ($_GET['timestamps']) $scraper->timestamp = $row['timestamp'];
	$scrapers[] = $scraper;
	if (!$mostRecentTime || $row['timestamp'] > $mostRecentTime) {
		$mostRecentTime = $row['timestamp'];
	}
}

echo json_encode(array('time' => $mostRecentTime, 'scrapers' => $scrapers));