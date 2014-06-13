<?php

require_once(__DIR__.'/AmazonECS.class.php');
require_once(__DIR__.'/Semantics3.php');


function google($opts) {
	if ($opts['sku'] && $opts['site'] == 'Amazon' && $opts['atmoic']) return array();
	// Restrictions
	foreach (array('upc' => 'gtin', 'brand', 'title') as $a => $b) {
		if (is_int($a)) {
			$a = $b;
		}

		if ($opts[$a]) {
			if (is_array($opts[$a])) {
				$value = implode('|', $opts[$a]);
			}
			else {
				$value = $opts[$a];
			}

			$restrictions[] = "$b:$value";
		}
	}

	// Query
	if ($opts['query']) {
		$query[] = $opts['query'];
	}
	if ($opts['model']) {
		$query[] = $opts['model'];
	}
	if ($opts['sku'] && $opts['site'] != 'Amazon') {
		$query[] = $opts['sku'];
	}

	if ($restrictions || $query) {
		$key = 'AIzaSyAiH3_YOkXGdFcm_JQgVjvx7qDIbNv4kyw';
		$url = "https://www.googleapis.com/shopping/search/v1/public/products?key=$key&country=US&alt=json";


		// Ordering
		if ($opts['orderBy']) {
			switch ($opts['orderBy']) {
				case 'relevancy': $url .= "&rankBy=relevancy"; break;
				case 'price': $url .= "&rankBy=price:ascending"; break;
			}
		}

		// Limit
		if ($opts['limit']) {
			$url .= "&maxResults=$opts[limit]";
		}
		else {
			$url .= "&maxResults=1000";
		}

		if ($restrictions) {
			$url .= "&restrictBy=" . urlencode(implode(',', $restrictions));
		}


		if ($query) {
			$url .= '&q=' . urlencode(implode(' ', $query));
		}

		$response = file_get_contents($url);
		return json_decode($response, true);
	}

	return array();
}

function semantics3($opts, &$sem3Id = null) {
	if ($opts['sku'] && $opts['site'] == 'Amazon' && $opts['atmoic']) return array();

	// Restrictions
	foreach (array('upc', 'brand', 'title' => 'name', 'model') as $a => $b) {
		if (is_int($a)) {
			$a = $b;
		}

		if ($opts[$a]) {
			if (is_array($opts[$a])) {
				$value = implode('||', $opts[$a]);
			}
			else {
				$value = $opts[$a];
			}

			$params[$b] = $value;
		}
	}


	if ($opts['query']) {
		$params['search'] = $opts['query'];
	}

	if ($opts['sku'] && $opts['site']) {
		$map = array(
			'Amazon' => 'amazon.com',
			'BestBuy' => 'bestbuy.com',
		);
		$params['sitedetails'] = array('name' => $map[$opts['site']], 'sku' => $opts['sku']);
	}


	if ($params) {
		$requestor = new Semantics3_Products('SEM3EE366BAD627DC64375357D7869A1A326', 'OGRlMzUxMTA3ZTVjMmE3NWI0NjFjN2RhNDhhOTQ4NDk');

		// Ordering
		if ($opts['orderBy']) {
			switch ($opts['orderBy']) {
				case 'price': 
					$params['sort'] = array('price' => 'asc');
			}
		}

		// Limit
		if ($opts['limit']) {
			$params['limit'] = $opts['limit'];
		}

		// var_dump($params);
		// exit;
		foreach ($params as $field => $value) {
			$requestor->products_field($field, $value);
		}



		$response = $requestor->get_products();

		$response = json_decode($response, true);
		$sem3Id = $response['results'][0]['sem3_id'];

		return $response;

	}

	return array();
}

function vigLinks($opts) {
	$key = 'd6acd66b192f09c62de870adc5ac4836';
	$url = "http://catalog.viglink.com/vigcatalog/products.json?key=$key";
	if ($opts['query']) {
		$url .= '&keyword=' . urlencode($opts['query']);
	}
	else if ($opts['upc']) {
		$url .= "&keyword_upc=$opts[upc]";
	}
	else {
		$query = array();
		if ($opts['brand']) {
			$query[] = "$opts[brand]";
		}
		if ($opts['title']) {
			$query[] = "$opts[title]";
		}
		if ($opts['model']) {
			$query[] = "$opts[model]";
		}
		if (!$query) return array();
		$url .= '&keyword=' . urlencode(implode(' ', $query));
	}
	$response = file_get_contents($url);
	return json_decode($response, true);
}

function amazon($opts) {
	if ($opts['sku'] && $opts['site'] != 'Amazon' && $opts['atmoic']) return array();

	$amazonEcs = new AmazonECS('AKIAJBNE52LZN5Y7NNOA', 'gPbBsnYJuaxPUwnfcBhaUkc7Dl09hpCcy80hNtYI', 'com', 'bagggit-20');
	$amazonEcs->category('All');

	$amazonEcs->responseGroup('ItemAttributes,Offers,Images');


	// switch ($opts['response']) {
	// 	case null:
	// 	case 'details':
	// 		$amazonEcs->responseGroup('ItemAttributes');
	// 		break;
	// 	case 'offers':
	// 		$amazonEcs->responseGroup('Offers');
	// 		break;
	// }

	if ($opts['upc']) {
		$upcChunks = array_chunk((array)$opts['upc'], 10);

		$results = array();
		foreach ($upcChunks as $upcs) {
			$results[] = $amazonEcs->optionalParameters(array(
				'IdType' => 'UPC',
				'SearchIndex' => 'All',
			))->lookUp(implode(',', $upcs));
		}
		return $results;
	}
	else if ($opts['ean']) {
		return array($amazonEcs->optionalParameters(array(
			'IdType' => 'EAN',
			'SearchIndex' => 'All',
		))->lookUp($opts['ean']));
	}
	else if ($opts['sku'] && $opts['site'] == 'Amazon') {
		return array($amazonEcs->lookUp($opts['sku']));
	}
	else {
		$query = array();
		foreach (array('brand', 'model', 'title', 'query') as $prop) {
			if ($opts[$prop]) {
				$query[] = $opts[$prop];
			}
		}
		if ($query) {
			return array($amazonEcs->search(implode(' ', $query)));	
		}
	}

	return array();
}

function shopping($opts) {
	$key = '75bfa010-1dd3-4a80-bca5-0864b8b2a135';
	$trackingId = '8078493';

	//"http://publisher.api.shopping.com/publisher/3.0/rest/GeneralSearch?apiKey=$key&trackingId=$trackingId&keyword="
}

function parseGoogleOffers($results, $opts = array()) {
	if ($opts['skip']) return $results;

	$offers = array();
	if ($results['items']) {
		foreach ($results['items'] as $item) {
			$offer = (array)$opts['extra'] + array(
				'title' => $item['product']['title'],
				'brand' => $item['product']['brand'],
				'site' => $item['product']['author']['name'],
				'price' => $item['product']['inventories'][0]['price'],
				'shipping' => $item['product']['inventories'][0]['shipping'],
				'tax' => $item['product']['inventories'][0]['tax'],
				'url' => $item['product']['link'],
				'condition' => $item['product']['condition'],
				'lastUpdated' => $item['product']['modificationTime'],
				'api' => 'google',
			);

			if ($item['product']['images']) {
				$offer['images'] = array_map(function ($img) { return $img['link']; }, $item['product']['images']);
			}

			if ($item['product']['gtin']) {
				$offer += array(
					'upc' => substr((string)$item['product']['gtin'], 2),
					'gtin' => $item['product']['gtin']
				);
			}


			if ($opts['groupByUpc']) {
				$offers[$offer['upc']][] = $offer;
			}
			else {
				$offers[] = $offer;
			}
		}
	}

	return $offers;
}

function parseSemantics3Offers($results, $opts = null) {
	if ($opts['skip']) return $results;
	$offers = array();
	if ($results['results'] && $results['results'][0]) {
		$result = $results['results'][0];

		if ($result['sitedetails']) foreach ($result['sitedetails'] as $site) {
			$siteOffer = $site['latestoffers'][0];
			if (!$siteOffer) continue;

			$conditionMap = array(
				'Used' => 'used',
				'New' => 'new',
				'Brand New Factory Sealed with Full USA Warranty' => 'new',
				'' => 'new',
			);
			
			$offer = array(
				'title' => $result['name'],
				'brand' => $result['brand'],
				'site' => $site['name'],
				'price' => $siteOffer['price'],
				'shipping' => $siteOffer['shipping'],
				'condition' => $conditionMap[$siteOffer['condition']],
				'url' => $site['url'],
				'lastUpdated' => $siteOffer['lastrecorded_at'],
				'api' => 'semantics3',
				'images' => $result['images']
			);

			if ($opts['groupByUpc']) {
				$offers[$result['upc']][] = $offer;
			}
			else {
				$offers[] = $offer;
			}
		}
	}
	return $offers;
}

function parseVigLinksOffers($results, $opts = null) {
	if ($opts['skip']) return $results;
	if ($results) {
		if ($results['resources']['merchants']['merchant']) {
			foreach ($results['resources']['merchants']['merchant'] as $merchant) {
				$merchants[$merchant['id']] = $merchant;
			}
		}

		// var_dump($merchants);

		$offerResults = $results['results']['products']['product'][0]['offers']['offer'];
		$products = $results['results']['products']['product'];
		$offers = array();


		if ($opts['groupByUpc']) {
			foreach ($results['parameters'] as $parameter) {
				if ($parameter['type'] == 'query' && $parameter['name'] == 'keyword_upc') {
					$upc = $parameter['value'];
					break;
				}
			}
		}

		if ($products) {
			foreach ($products as $product) {
				if ($product['offers']['offer']) {
					foreach ($product['offers']['offer'] as $offer) {
						if ($offer['currency_iso'] == 'USD') {
							$o = (array)$opts['extra'] + array(
								'title' => $offer['name'],
								'site' => $merchants[$offer['merchant']]['name'],
								'price' => $offer['price_merchant'],
								'url' => $offer['url'],
								'api' => 'vigLinks',
								'images' => array($offer['image_url_large']),
							);

							if ($opts['groupByUpc']) {
								$offers[$upc][] = $o;
							}
							else {
								$offers[] = $o;
							}
						}
					}
				}
			}
		}

		return $offers;
	}
	else {
		return array();
	}
}

function parseAmazonOffers($resultsList, $opts = null) {
	if ($opts['skip']) return $resultsList;

	$offers = array();

	foreach ($resultsList as $results) {
		if (is_array($results->Items->Item)) {
			$items = $results->Items->Item;
		}
		else {
			$items = array($results->Items->Item);
		}

		foreach ($items as $Item) {
			// var_dump($Item);exit;
			$offerBase = (array)$opts['extra'] + array(
				'title' => $Item->ItemAttributes->Title,
				'api' => 'amazon',
				'lastUpdated' => gmdate('Y-m-d H:i:s'),
			);

			if ($Item->ImageSets->ImageSet) {
				if (is_array($Item->ImageSets->ImageSet)) {
					$ImageSet = $Item->ImageSets->ImageSet;
				}
				else {
					$ImageSet = array($Item->ImageSets->ImageSet);
				}
				$offerBase['images'] = array_map(function($i) { return $i->SmallImage->URL; }, $ImageSet);
			}

			if ($Item->ItemAttributes->UPC) {
				$offerBase['upc'] = $Item->ItemAttributes->UPC;
			}
			if ($Item->ItemAttributes->EAN) {
				$offerBase['ean'] = $Item->ItemAttributes->EAN;
			}
			if ($Item->ItemAttributes->ISBN) {
				$offerBase['isbn'] = $Item->ItemAttributes->ISBN;
			}
			if ($Item->ItemAttributes->Brand) {
				$offerBase['brand'] = $Item->ItemAttributes->Brand;
			}

			$theseOffers = array();

			if ($Item->Offers->Offer->OfferListing->Price->Amount) {
				$theseOffers[] = $offerBase + array(
					'site' => 'Amazon',
					'price' => $Item->Offers->Offer->OfferListing->Price->Amount/100,
					'condition' => 'new',
					'url' => $Item->DetailPageURL,
				);
			}

			if ($Item->OfferSummary->LowestNewPrice->Amount && $Item->OfferSummary->LowestNewPrice->Amount != $Item->Offers->Offer->OfferListing->Price->Amount) {
				$theseOffers[] = $offerBase + array(
					'site' => 'Amazon Marketplace',
					'price' => $Item->OfferSummary->LowestNewPrice->Amount/100,
					'condition' => 'new',
					'url' => $Item->Offers->MoreOffersUrl,
				);
			}

			if ($Item->OfferSummary->LowestUsedPrice->Amount) {
				$theseOffers[] = $offerBase + array(
					'site' => 'Amazon Marketplace',
					'price' => $Item->OfferSummary->LowestUsedPrice->Amount/100,
					'condition' => 'used',
					'url' => $Item->Offers->MoreOffersUrl,
				);
			}

			if ($opts['groupByUpc']) {
				$offers[$offerBase['upc']] = $theseOffers;
			}
			else {
				$offers = array_merge($offers, $theseOffers);
			}
		}
	}
	return $offers;
}

function uniqueOffers($offers) {
	$map = array();
	foreach ($offers as $offer) {
		$key = "$offer[upc]|$offer[site]|$offer[price]|$offer[condition]";
		if (!$map[$key]) $map[$key] = $offer;
	}
	return array_values($map);
}

function filterOffers($query, $offers, &$excludedOffers = null) {
	$offers = uniqueOffers($offers);
	// return $offers;
	if ($query) {
		$newOffers = array();
		foreach ($offers as $offer) {
			if ($offer['brand'] && $query['brand']) {
				if (stripos($offer['brand'], $query['brand']) === false) {
					// var_dump($offer);
					if ($excludedOffers) {
						$excludedOffers[] = $offer;
					}
					continue;
				}
			}
			$newOffers[] = $offer;

		}
		return $newOffers;
	}
	else {
		return $offers;
	}
}

function sortOffers(&$offers) {
	usort($offers, function($a, $b) { return $a['price']*100 - $b['price']*100; });
}

function group($offers, $groups, $level = 0) {
	$grouped = array();
	foreach ($offers as $offer) {
		$prop = $groups[$level];
		$grouped[$offer[$prop]][] = $offer;
	}

	++ $level;
	if ($level < count($groups)) {
		foreach ($grouped as $key => $group) {
			$grouped[$key] = group($group, $groups, $level);
		}
	}

	return $grouped;
}

function get($opts, &$sem3Id = null) {
	$query = $opts['query'];

	foreach ($query as $name => $value) {
		$query[$name] = str_replace('/', '', preg_replace("/&#?[a-z0-9]{2,8};/i","",$value));
	}

	if ($opts['apis']) {
		$apis = array_flip($opts['apis']);
		foreach ($opts['apis'] as $api) {
			$enabled[$api] = true;
		}
	}
	else {
		foreach (array('amazon', 'vigLink', 'semantics3') as $api) {
			$enabled[$api] = true;
		}
	}

	if ($query['brand']) {
		$postQuery['brand'] = $query['brand'];
	}
	if ($query['model']) {
		$postQuery['model'] = $query['model'];
	}

	if ($query['sku']) {
		if ($query['brand']) {
			unset($query['brand']);
		}
		if ($query['model']) {
			unset($query['model']);
		}
	}

	$trace = true;

	$results = array();
	if ($enabled['amazon'])
		$responses['amazon'] = parseAmazonOffers(amazon($query), array('groupByUpc' => $opts['groupByUpc']) + ($trace ? array('extra' => array('phase' => 1, 'query' => $query)) : array()));

	if ($enabled['google'])
		$responses['google'] = parseGoogleOffers(google($query), array('groupByUpc' => $opts['groupByUpc']) + ($trace ? array('extra' => array('phase' => 1, 'query' => $query)) : array()));

	if ($enabled['semantics3'])
		$responses['semantics3'] = parseSemantics3Offers(semantics3($query, $sem3Id), array('groupByUpc' => $opts['groupByUpc']) + ($trace ? array('extra' => array('phase' => 1, 'query' => $query)) : array()));

	if ($enabled['vigLinks'])
		$responses['vigLinks'] = parseVigLinksOffers(vigLinks($query), array('groupByUpc' => $opts['groupByUpc']) + ($trace ? array('extra' => array('phase' => 1, 'query' => $query)) : array()));

	if ($responses) {
		if ($opts['groupByUpc']) {
			foreach ($responses as $res) {
				foreach ($res as $upc => $offers) {
					if ($offers) {
						$results[$upc] = array_merge((array)$results[$upc], $offers);
					}
				}
			}
			$newResults = array();
			foreach ($results as $upc => $offers) {
				$offers = filterOffers($postQuery, $offers);
				if ($offers) {
					$newResults[$upc] = $offers;
				}
			}
			$results = $newResults;
		}
		else {
			foreach ($responses as $offers) {
				$results = array_merge($results, $offers);
			}
			$results = filterOffers($postQuery, $results);
		}

		if ($opts['groupByUpc']) {
			$upcs = array_keys($results);
		}
		else {
			$upcs = array();
			foreach ($results as $offer) {
				$upcs[$offer['upc']] = true;
			}
			$upcs = array_keys($upcs);
		}

		$upcs = array_filter($upcs, function($upc) { return $upc; });

		$query = array('upc' => $upcs);
		// if ($opts['query']['brand']) {
		// 	$query['brand'] = $opts['query']['brand'];
		// }

		$responses = array();
		if ($enabled['google'])
			$responses['google'] = parseGoogleOffers(google($query), array('groupByUpc' => $opts['groupByUpc']) + ($trace ? array('extra' => array('phase' => 2, 'query' => $query)) : array()));

		if ($enabled['amazon'])
			$responses['amazon'] = parseAmazonOffers(amazon($query), array('groupByUpc' => $opts['groupByUpc']) + ($trace ? array('extra' => array('phase' => 2, 'query' => $query)) : array()));

		if ($opts['groupByUpc']) {
			foreach ($responses as $res) {
				foreach ($res as $upc => $offers) {
					$results[$upc] = filterOffers($postQuery, array_merge((array)$results[$upc], $offers));
				}
			}

			foreach ($results as $upc => &$offers) {
				sortOffers($offers);
			}
		}
		else {
			foreach ($responses as $offers) {
				$results = array_merge($results, $offers);
			}

			$results = filterOffers($postQuery, $results);
			sortOffers($results);


			if ($groupBy = (array)$opts['groupBy']) {
				$results = group($results, $groupBy);
			}
		}
	}

	return $results;
}
