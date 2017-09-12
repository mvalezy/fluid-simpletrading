<?php

require_once('config/config.inc.php');
require_once('functions.inc.php'); 
require_once('config/kraken.api.config.php'); 
require_once('config/nma.api.config.php'); 
require_once('class/alert.class.php'); 

// Open SQL connection
$db = connecti();

// Query Data
if(isset($_GET['debug']) && $_GET['debug'] > 0) { $debug = $_GET['debug']; }
else $debug = 0;

/*
 * CRON CONFIG
 * 30 seconds
 * * * * * * /usr/bin/php6 /kunden/homepages/23/d202161969/htdocs/demo/trade/pulse.php > /kunden/homepages/23/d202161969/htdocs/demo/trade/log/cron.log 2>&1
/*

/*
 * PULSE
 * Recommended cycle : 1 min
*/


/*
 * TICKER
 * QUERY Current Price
*/
$res = $kraken->QueryPublic('Ticker', array('pair' => TRADE_PAIR ));
if(isset($res['result'])) {
    if(isset($res['result'][TRADE_PAIR]['c']['0'])) {
        $price = $res['result'][TRADE_PAIR]['c']['0'];

        echo "Ticker:".TRADE_PAIR."=".$price."\n";
        
        // Store Price
        $query_ins = "INSERT INTO trade_history SET echange = '".TRADE_EXCHANGE."', pair = '".TRADE_PAIR."', price = '$price' ;";
        $sql_ins = $db->query($query_ins);
        mysqlerr($db, $query_ins);
    }
}

//$query = "SELECT id FROM trade_history WHERE addDate > (NOW() - INTERVAL ($ticker*60) SECOND)";

/*
 * ALERT
 * SEND Notifications
*/

$Alert = new Alert();
$Alert->select($price);

if(is_array($Alert->List) && count($Alert->List) > 0) {
    foreach($Alert->List as $id => $detail) {
        $Alert->send($id);
        echo "Alert:send=".$detail->price."(".$id.")\n";
    }
}



// Display Loading time
$loading_time = microtime();
$loading_time = explode(' ', $loading_time);
$loading_time = $loading_time[1] + $loading_time[0];
$loading_finish = $loading_time;
$loading_total_time = round(($loading_finish - $loading_start), 4);
?>

Last execution:<?php echo date('Y-m-d H:i:s'); ?> - Load:<?php echo $loading_total_time; ?>s