<?php
header('Content-Type: text/plain');

require_once('../includes/Semantics3.php');


$requestor = new Semantics3_Products('SEM3EE366BAD627DC64375357D7869A1A326', 'OGRlMzUxMTA3ZTVjMmE3NWI0NjFjN2RhNDhhOTQ4NDk');
$requestor->offers_field('sem3_id', '0UpGgUOb7aKkiyoigkMcOq');
// $requestor->offers_field("lastrecorded_at", "gte", 1348654600);
// $requestor->offers_field("limit", 30);
// $requestor->limit(40);

// $requestor->offers_field('offers_total', 20);

var_dump($requestor->get_offers());

// return;

// Newegg: 8438988
// Zappos: 15872

require_once('../includes/productComparison.php');


var_dump(semantics3(array(
	// 'brand' => 'ASUS',
	// 'model' => 'HD7770-2GD5'
	// 'brand' => 'TYR',
	'site' => 'Amazon',
	'sku' => 'B00BZCXZ0E'
)));

return;

// $amazon = true;
// $google = true;
// $vigLinks = true;
$semantics3 = true;
// $get = true;

if ($amazon) {
	var_dump(parseAmazonOffers(amazon(array(
'sku' => 'N82E16811855004',
'brand' => 'CFI',
'model' => 'CFI-A5396',
'site' => 'Newegg',
	)), array('skip' => false, 'groupByUpc' => false)));
}


if ($google) {
	var_dump(parseGoogleOffers(google(array(
// 'sku' => 'N82E16811855004',
'brand' => 'CFI',
'model' => 'CFI-A5396',
// 'site' => 'Newegg',

	)), array('skip' => true, 'groupByUpc' => false)));
}

if ($semantics3) {
	var_dump(parseSemantics3Offers(semantics3(array(
		'brand' => 'ASUS',
		'model' => 'HD7770-2GD5'
	)), array('skip' => false, 'groupByUpc' => true)));
}

if ($vigLinks) {
	var_dump(parseVigLinksOffers(vigLinks(array(
		// 'site' => 'Newegg',
		// 'upc' => '036702119443'
		// 'query' => 'i5-3570K',
		// 'upc' => '889830310448'

		'brand' => 'TYR',
		'title' => 'Solid Reversible Diamondfit'

	)), array('skip' => false, 'groupByUpc' => true)));
}

if ($get) {
	var_dump(get(array(
		// 'groupByUpc' => true,
		'groupBy' => array('condition'),
		'apis' => array('google', 'amazon'),
		'query' => array(
			'brand' => 'SAMSUNG',
			'sku' => 'N82E16889102673',
			'site' => 'Newegg'
		),
		'postQuery' => array(
		)
	)));
}
