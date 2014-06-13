<?php

require_once('includes/header.php');
require_once('includes/user.php');

echo userId();


echo decryptString(encryptString('test', '$!|i8>-8[5~WAaE'), '$!|i8>-8[5~WAaE');