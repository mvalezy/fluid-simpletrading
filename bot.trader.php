<?php

require('depedencies.inc.php');

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = (int) $_GET['debug']; }
else $debug = 1;


$Exchange = new Exchange();
$Response = $Exchange->API->QueryPrivate('ClosedOrders', array());
krumo($Response);

exit;

$History = new History();
$History->select(30);

krumo($History);

$sma = trader_sma($History->List, 30);

krumo($sma);


?>