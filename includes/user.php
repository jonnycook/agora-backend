<?php

function passHash($password) {
	$salt = ':Y_</Xg,2_c&2CE\'";J.|{3,k3{O4~^+<#i\'}?F@]:W*>H[:?.7a~~$!>Pk[#eN\'3>S_8#w|';
	return sha1($password.$salt);
}

function encryptString($string, $key) {
	$iv = mcrypt_create_iv(
	    mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC),
	    MCRYPT_DEV_URANDOM
	);

	return base64_encode(
	    $iv .
	    mcrypt_encrypt(
	        MCRYPT_RIJNDAEL_256,
	        hash('sha256', $key, true),
	        $string,
	        MCRYPT_MODE_CBC,
	        $iv
	    )
	);
}

function decryptString($string, $key) {
	$data = base64_decode($string);
	$iv = substr($data, 0, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC));

	return rtrim(
	    mcrypt_decrypt(
	        MCRYPT_RIJNDAEL_256,
	        hash('sha256', $key, true),
	        substr($data, mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)),
	        MCRYPT_MODE_CBC,
	        $iv
	    ),
	    "\0"
	);
}

if (devEnv()) {
	function userId() {
		return $_COOKIE['userId'];
	}
	function setUserId($id) {
		setcookie('userId', $id, time() + 60*60*24*30*3, '/', '.agora.dev');
	}
}
else {
	function userId() {
		$userId = $_COOKIE['userId'];
		if ($userId && !is_numeric($userId)) {
			return decryptString($_COOKIE['userId'], '$!|i8>-8[5~WAaE');
		}
	}
	function setUserId($id) {
		setcookie('userId', encryptString($id, '$!|i8>-8[5~WAaE'), time() + 60*60*24*30*3, '/', '.agora.sh');
	}
}



