<?php

$garbledId = '72831c';
$id = '';
$hashPart = '';
for ($i = 0; $i < strlen($garbledId); ++ $i) {
	if ($i % 2) {
		$hashPart .= $garbledId[$i];
	}
	else {
		$id .= $garbledId[$i];
	}
}

$hash = md5($id . 'salty apple sauce');

var_dump($id);
var_dump($hashPart);
var_dump($hash);