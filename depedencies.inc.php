<?php

// CONFIG
require_once('config/config.inc.php');

// UTILS AND THIRD PARTY
require_once('functions.inc.php'); 
require_once('config/kraken.api.config.php'); 
require_once('config/nma.api.config.php'); 

// Class
require_once('class/error.class.php'); 
require_once('class/user.class.php');
require_once('class/history.class.php'); 
require_once('class/ledger.class.php'); 
require_once('class/alert.class.php');

// API
require_once('api/kraken.api.php');
require_once('api/nma.api.php');


// Open SQL connection
$db = connecti();

?>