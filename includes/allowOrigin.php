<?php

if (ENV == 'LOCAL_DEV') {
	header('Access-Control-Allow-Origin: http://webapp.agora.dev');
}
else if (ENV == 'PROD') {
	if (defined('TESTING')) {
		header('Access-Control-Allow-Origin: http://webapp.agora.dev');
	}
	else {
		header('Access-Control-Allow-Origin: http://agora.sh');		
	}
}
header('Access-Control-Allow-Credentials: true');
