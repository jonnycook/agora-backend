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

// $bucket->newObject($id, $this->riakData())->store();
$riak->bucket('test')->newObject('test', 'test')->store();

$bucket = $riak->bucket('test');
var_dump($bucket->get('test'));


