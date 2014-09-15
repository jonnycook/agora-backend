<?php
header('Content-Type: application/json');
$query = urlencode($_GET['query']);
$result = json_decode(file_get_contents("http://sgt.io/api?query=iphone&username=agora&api_key=387e06f95186087f4039912efeed3bf21f4d76dd&pretty_print=true&query=$query"), true);

foreach ($result['deals'] as $deal) {
	$deals[$deal['id']] = $deal;
}


echo json_encode(array_values($deals));