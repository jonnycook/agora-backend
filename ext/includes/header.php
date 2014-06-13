<?php

// require_once(__DIR__.'/../../includes/header.php');
// require_once(__DIR__.'/../../includes/sites.php');
require_once(__DIR__.'/../../includes/header.php');
require_once(__DIR__.'/../../includes/user.php');

if (defined('SHARED_PATH')) {
	require_once(SHARED_PATH . 'sites.php');	
}

function remoteAddr() {
	return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
}


function mongoClient() {
	global $mongo;
	if (!$mongo) {
		$mongo = new MongoClient();
	}
	return $mongo;
}

function mongoDb() {
	return mongoClient()->{MONGO_DB};
}

require_once(__DIR__.'/../../includes/Basho/Riak/Riak.php');
require_once(__DIR__.'/../../includes/Basho/Riak/Bucket.php');
require_once(__DIR__.'/../../includes/Basho/Riak/Exception.php');
require_once(__DIR__.'/../../includes/Basho/Riak/Link.php');
require_once(__DIR__.'/../../includes/Basho/Riak/MapReduce.php');
require_once(__DIR__.'/../../includes/Basho/Riak/Object.php');
require_once(__DIR__.'/../../includes/Basho/Riak/StringIO.php');
require_once(__DIR__.'/../../includes/Basho/Riak/Utils.php');
require_once(__DIR__.'/../../includes/Basho/Riak/Link/Phase.php');
require_once(__DIR__.'/../../includes/Basho/Riak/MapReduce/Phase.php');


function riakClient() {
	global $riak;
	if (!$riak) {
		$riak = new Basho\Riak\Riak(RIAK_HOST, 10018);
	}
	return $riak;
}