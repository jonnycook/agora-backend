<?php

require_once('includes/Basho/Riak/Riak.php');
require_once('includes/Basho/Riak/Bucket.php');
require_once('includes/Basho/Riak/Exception.php');
require_once('includes/Basho/Riak/Link.php');
require_once('includes/Basho/Riak/MapReduce.php');
require_once('includes/Basho/Riak/Object.php');
require_once('includes/Basho/Riak/StringIO.php');
require_once('includes/Basho/Riak/Utils.php');
require_once('includes/Basho/Riak/Link/Phase.php');
require_once('includes/Basho/Riak/MapReduce/Phase.php');

// header('Content-Type: text/plain');

$riak = new Basho\Riak\Riak('50.116.31.117', 10018);

$bucket = $riak->bucket('1.products');
var_dump($bucket->get('0:348'));
