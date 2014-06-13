<?php


require_once('includes/header.php');
$nameFiles = glob('names/*');

set_time_limit(0);

// foreach ($nameFiles as $nameFile) {
// 	$file = file_get_contents($nameFile);
// 	$lines = explode("\n", $file);

// 	foreach ($lines as $line) {
// 		$cols = explode(',', $line);
// 		var_dump($cols);

// 		$name = mysql_real_escape_string($cols[0]);
// 		$sql = "INSERT INTO `names` SET `name` = '$name', `sex` = \"$cols[1]\", `occurrences` = $cols[2]";
// 		mysql_query($sql) or die(mysql_error());
// 	}
// }

$contents = file_get_contents('Prenoms.txt');

$lines = explode("\n", $contents);

foreach ($lines as $line) {
	$cols = explode("\t", $line);
	$name = mysql_real_escape_string($cols[0]);
	$genders = explode(',', $cols[1]);

	foreach ($genders as $gender) {
		$sql = "INSERT INTO names SET name = '$name', sex = '$gender'";
		mysql_query($sql) or die(mysql_error());
	}
}