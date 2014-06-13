<?php

require_once('includes/header.php');
require_once('includes/parse.php');


set_time_limit(3);

header('Content-Type: text/plain');
ob_implicit_flush(true);


$descriptors = array(
	// 'wedding ring',
	'asdf',
	'present for betty',

	'best nice, sturdy and warm winter boots that are wool for hiking in Alaska for husbands 30th birthday',
	'best nice, sturdy and warm winter boots that are wool for hiking in Alaska for great great grandfather-in-laws 102nd birthday',
	// 'best nice, sturdy and warm winter boots for hiking in Alaska for husbands birthday',
	// 'book for wifes birthday',
	// 'best camera for black-and-white photography for my sisters birthday',
	// 'PS3 games for nephew',
	// 'reusable water bottles that are easy to clean for my brothers 27st birthday for beginner',
	// 'book for a birthday present for 7 year old niece',
	// 'present for 7 year old daughter\'s birthday',
	// 'good guitar for beginners',
	// 'present for 7 year old grandmother-in-law\'s birthday',
	// 'birthday present for 7 year old son-in-law',

	// 'shoes for great grandmother',
	// 'shoes for dad',
	// 'shoes for great grandmother-in-law',


	// 'shoes for my brother',



	// 'water-proof shoes',

	// 'best boots for hiking',
	// 'best winter boots for hiking',
	// 'bird-watching binoculars',
	// 'tent for camping in winter',
	// 'simple, minimal, sturdy money clip',

	// 'snow tires for bicycle',

	// 'snow tires for winter bicycling' ,

	// 'dinosaur fabric for bowties',

	// 'dinosaur fabric for Hazel',

	// 'keyboard case for nexus 10',

	// 'christmas present for girlfriend'
);

foreach ($descriptors as $descriptor) {
	echo ">$descriptor\n";
	var_dump(parse($descriptor));
}

