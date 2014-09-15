<?php
header('Content-Type: application/json');
require_once('../includes/productComparison.php');

header('Access-Control-Allow-Origin: http://webapp.agora.dev');
header('Access-Control-Allow-Credentials: true');

foreach ($_GET as $name => $value) {
	$query[$name] = str_replace('/', '', preg_replace("/&#?[a-z0-9]{2,8};/i","",$value));
}

$result = get(array(
	// 'groupByUpc' => true,
	'groupBy' => array('condition'),
	// 'apis' => array('google'),
	'query' => $query
), $sem3Id);

echo json_encode(array('products' => $result, 'sem3Id' => $sem3Id));