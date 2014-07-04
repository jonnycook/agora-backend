<?php

error_reporting(E_ALL ^ E_NOTICE);

if (file_exists(__DIR__.'/env.php')) {
	require_once(__DIR__.'/env.php');
}

if (!defined('ENV')) {
	if ($_ENV['AGORA_ENV']) {
		define('ENV', $_ENV['AGORA_ENV']);
	}
	else if (getenv('AGORA_ENV')) {
		define('ENV', getenv('AGORA_ENV'));
	}
	else {
		define('ENV', 'LOCAL_DEV');
	}
}

switch (ENV) {
	case 'LOCAL_DEV':
		$mysqli = mysqli_connect('127.0.0.1', 'root', '');
		mysqli_select_db($mysqli, 'agora');
		define('SHARED_PATH', '/web/agora-shared/');
		define('USE_RIAK', false);
		define('MONGO_DB', 'agora');
		define('SITE_DOMAIN', 'agora.dev');
		define('GATEWAY', 'localhost')

		break;


	case 'TEST':
		define('TESTING', true);
		$mysqli = mysqli_connect('127.0.0.1', 'root', '');
		mysqli_select_db($mysqli, 'agora_test');
		define('SHARED_PATH', '/web/agora-shared/');
		define('USE_RIAK', false);
		define('MONGO_DB', 'agora');
		break;

	// case 'LINODE_DEV':
	// 	$mysqli = mysqli_connect('127.0.0.1', 'root', 'ghijkk56k');
	// 	mysqli_select_db($mysqli, 'agora_dev');
	// 	define('SHARED_PATH', '/var/www/shared/');
	// 	define('USE_RIAK', false);
	// 	define('MONGO_DB', 'agora_dev');
	// 	break;
	case 'LINODE_DEV':
		$mysqli = mysqli_connect('127.0.0.1', 'root', 'ghijkk56k') or die(mysqli_error($mysqli));
		mysqli_select_db($mysqli, 'agora');
		define('SHARED_PATH', '/var/www/shared/');
		define('USE_RIAK', false);
		define('MONGO_DB', 'agora');
		// define('COOKIE_DOMAIN', 'agora_dev');
		break;
		
	case 'MESSAGE_SERVER':
		$mysqli = mysqli_connect('50.116.31.117', 'root', 'ghijkk56k');
		mysqli_select_db($mysqli, 'agora');
		break;
		
	case 'PROD':
		$mysqli = mysqli_connect('50.116.31.117', 'root', 'ghijkk56k')  or die(mysqli_error($mysqli));
		mysqli_select_db($mysqli, 'agora');
		define('SHARED_PATH', '/var/www/shared/');
		define('USE_RIAK', true);
		define('RIAK_HOST', '50.116.31.117');
		define('MONGO_DB', 'agora_prod');
		define('SITE_DOMAIN', 'agora.sh');

		define('GATEWAY', '50.116.26.9')
		break;
}

function devEnv() {
	return ENV == 'LOCAL_DEV' || ENV == 'LINODE_DEV';
}

