<?php

function gatewayServer($userId) {
	return 'localhost:3000';
}

function sendMessage($userId, $type, $args) {
	$gatewayServer = gatewayServer($userId);
	$ch = curl_init("http://$gatewayServer/$type");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
	curl_exec($ch);
}

function sendUpdate($userId, $changes) {
	sendMessage($userId, 'update', array(
		'clientId' => 'Carl Sagan',
		'userId' => $userId,
		'changes' => json_encode($changes),
	));
}

