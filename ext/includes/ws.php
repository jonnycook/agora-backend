<?php

if (ENV == 'PROD') {
	$gatewayServers = array(
		'1' => '50.116.26.9:3000',
		'2' => '198.58.119.227:3000'
	);
	function gatewayServer($userId) {
		global $mysqli, $_gatewayServerByUserId, $gatewayServers;
		if ($_gatewayServerByUserId[$userId]) {
			return $gatewayServers[$_gatewayServerByUserId[$userId]];
		}
		else {
			$row = mysql_fetch_assoc(mysqli_query("SELECT gateway_server FROM m_users WHERE id = $userId"));
			$_gatewayServerByUserId[$userId] = $row['gateway_server'];
			return $gatewayServers[$row['gateway_server']];
		}
	}	
}

else {
	function gatewayServer($userId) {
		return GATEWAY . ':3000';
	}	
}

function sendMessage($userId, $type, $args) {
	$gatewayServer = gatewayServer($userId);
	$ch = curl_init("http://$gatewayServer/$type");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
